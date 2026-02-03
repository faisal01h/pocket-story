<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Http\Request;
use Xendit\Configuration;
use Xendit\Customer\CustomerApi;
use Xendit\Customer\CustomerRequest;
use Xendit\Invoice\CreateInvoiceRequest;
use Xendit\Invoice\InvoiceApi;

class XenditService
{
    protected InvoiceApi $invoiceApi;

    protected CustomerApi $customerApi;

    public function __construct()
    {
        Configuration::setXenditKey(config('services.xendit.secret_key'));

        $this->invoiceApi = new InvoiceApi;
        $this->customerApi = new CustomerApi;
    }

    /**
     * Create a Xendit customer for the user.
     */
    public function createCustomer(User $user): ?string
    {
        try {
            $customerRequest = new CustomerRequest([
                'reference_id' => 'user_'.$user->id,
                'email' => $user->email,
                'given_names' => $user->name,
                'mobile_number' => $user->phone ?? null,
                'type' => 'INDIVIDUAL',
            ]);

            $customer = $this->customerApi->createCustomer($customerRequest);

            return $customer['id'];
        } catch (\Exception $e) {
            \Log::error('Xendit customer creation failed: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Create a payment invoice for the subscription.
     */
    public function createInvoice(UserSubscription $subscription): array
    {
        $user = $subscription->user;
        $plan = $subscription->subscriptionPlan;

        // Create or retrieve Xendit customer
        $customerId = $subscription->xendit_customer_id ?? $this->createCustomer($user);

        if ($customerId) {
            $subscription->update(['xendit_customer_id' => $customerId]);
        }

        try {
            $invoiceRequest = new CreateInvoiceRequest([
                'external_id' => 'subscription_'.$subscription->id.'_'.time(),
                'amount' => $plan->price,
                'payer_email' => $user->email,
                'description' => "Subscription: {$plan->name} - {$plan->billing_period}",
                'customer_id' => $customerId,
                'currency' => $plan->currency,
                'invoice_duration' => 86400, // 24 hours
                'success_redirect_url' => route('subscription.show'),
                'failure_redirect_url' => route('subscription.index'),
                'items' => [
                    [
                        'name' => $plan->name,
                        'quantity' => 1,
                        'price' => $plan->price,
                        'category' => 'Subscription',
                    ],
                ],
                'metadata' => [
                    'user_id' => $user->id,
                    'subscription_id' => $subscription->id,
                    'plan_slug' => $plan->slug,
                ],
            ]);

            $invoice = $this->invoiceApi->createInvoice($invoiceRequest);

            return [
                'success' => true,
                'invoice_id' => $invoice['id'],
                'invoice_url' => $invoice['invoice_url'],
                'expires_at' => $invoice['expiry_date'],
            ];
        } catch (\Exception $e) {
            \Log::error('Xendit invoice creation failed: '.$e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Retrieve invoice details from Xendit.
     */
    public function getInvoice(string $invoiceId): ?array
    {
        try {
            $invoice = $this->invoiceApi->getInvoiceById($invoiceId);

            return $invoice;
        } catch (\Exception $e) {
            \Log::error('Xendit get invoice failed: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Verify webhook signature from Xendit.
     */
    public function verifyWebhookSignature(Request $request): bool
    {
        $webhookToken = config('services.xendit.webhook_token');

        if (! $webhookToken) {
            \Log::warning('Xendit webhook token not configured');

            return false;
        }

        $callbackToken = $request->header('X-Callback-Token');

        if (! $callbackToken) {
            \Log::warning('Missing X-Callback-Token header in webhook request');

            return false;
        }

        return hash_equals($webhookToken, $callbackToken);
    }

    /**
     * Calculate subscription expiry date based on billing period.
     */
    public function calculateExpiryDate(string $billingPeriod, ?\DateTime $startsAt = null): \DateTime
    {
        $startsAt = $startsAt ?? new \DateTime;
        $expiresAt = clone $startsAt;

        return match ($billingPeriod) {
            'weekly' => $expiresAt->modify('+1 week'),
            'monthly' => $expiresAt->modify('+1 month'),
            'yearly' => $expiresAt->modify('+1 year'),
            default => $expiresAt->modify('+1 month'),
        };
    }
}
