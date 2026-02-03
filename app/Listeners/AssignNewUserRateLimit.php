<?php

namespace App\Listeners;

use App\Events\UserRegistered;
use App\Jobs\UpdateUserRateLimitJob;
use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;

class AssignNewUserRateLimit
{
    /**
     * Create the event listener.
     */
    public function __construct() {}

    /**
     * Handle the event.
     */
    public function handle(UserRegistered $event): void
    {
        $freePlan = SubscriptionPlan::where('slug', 'free')->first();

        if (! $freePlan) {
            \Log::error('Free subscription plan not found');

            return;
        }

        // Create free tier subscription for new user
        UserSubscription::create([
            'user_id' => $event->user->id,
            'subscription_plan_id' => $freePlan->id,
            'status' => 'active',
            'starts_at' => now(),
            'expires_at' => null, // Free tier never expires
        ]);

        // Dispatch job to create rate limits
        UpdateUserRateLimitJob::dispatch($event->user->id);
    }
}
