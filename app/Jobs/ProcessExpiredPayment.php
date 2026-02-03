<?php

namespace App\Jobs;

use App\Models\SubscriptionTransaction;
use App\Models\UserSubscription;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessExpiredPayment implements ShouldQueue
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

        // Find the subscription
        $subscription = UserSubscription::where('xendit_invoice_id', $invoiceId)->first();

        if (! $subscription) {
            \Log::warning("Subscription not found for expired invoice: {$invoiceId}");

            return;
        }

        // Update subscription status to expired
        $subscription->update([
            'status' => 'expired',
        ]);

        // Update  transaction record
        SubscriptionTransaction::where('xendit_invoice_id', $invoiceId)
            ->update([
                'status' => 'failed',
                'failed_at' => now(),
            ]);

        // Revert user back to free tier
        UpdateUserRateLimitJob::dispatch($subscription->user_id);

        \Log::info("Subscription {$subscription->id} marked as expired");
    }
}
