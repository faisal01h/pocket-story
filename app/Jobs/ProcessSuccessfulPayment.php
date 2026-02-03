<?php

namespace App\Jobs;

use App\Models\SubscriptionTransaction;
use App\Models\UserSubscription;
use App\Services\XenditService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessSuccessfulPayment implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $webhookData
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $invoiceId = $this->webhookData['id'];
        $metadata = $this->webhookData['metadata'] ?? [];

        // Find the subscription
        $subscription = UserSubscription::where('xendit_invoice_id', $invoiceId)->first();

        if (! $subscription) {
            \Log::warning("Subscription not found for invoice: {$invoiceId}");

            return;
        }

        // Update subscription status to active
        $xenditService = new XenditService;
        $expiresAt = $xenditService->calculateExpiryDate(
            $subscription->subscriptionPlan->billing_period,
            now()
        );

        $subscription->update([
            'status' => 'active',
            'starts_at' => now(),
            'expires_at' => $expiresAt,
        ]);

        // Create or update transaction record
        SubscriptionTransaction::updateOrCreate(
            ['xendit_invoice_id' => $invoiceId],
            [
                'user_subscription_id' => $subscription->id,
                'amount' => $this->webhookData['amount'],
                'currency' => $this->webhookData['currency'] ?? 'IDR',
                'status' => 'paid',
                'xendit_invoice_url' => $this->webhookData['invoice_url'] ?? null,
                'payment_method' => $this->webhookData['payment_method'] ?? null,
                'payment_channel' => $this->webhookData['payment_channel'] ?? null,
                'paid_at' => now(),
                'metadata' => $this->webhookData,
            ]
        );

        // Update user's rate limits
        UpdateUserRateLimitJob::dispatch($subscription->user_id);

        \Log::info("Subscription {$subscription->id} activated successfully");
    }
}
