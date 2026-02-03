<?php

namespace App\Jobs;

use App\Models\LlmLimit;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class UpdateUserRateLimitJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $userId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $user = User::find($this->userId);

        if (! $user) {
            \Log::warning("User {$this->userId} not found for rate limit update");

            return;
        }

        // Get user's current subscription plan (or free plan)
        $plan = $user->getSubscriptionPlan();

        if (! $plan) {
            \Log::warning("No subscription plan found for user {$this->userId}");

            return;
        }

        // Deactivate all existing limits for this user
        LlmLimit::where('user_id', $user->id)->update(['is_active' => false]);

        // Calculate reset time
        $resetsAt = $this->calculateResetTime($plan);

        // Create new limit based on subscription plan
        LlmLimit::create([
            'user_id' => $user->id,
            'llm_model_id' => null, // Applies to all models
            'period' => $plan->rate_limit_period_hours ? 'hourly' : 'daily',
            'period_hours' => $plan->rate_limit_period_hours,
            'max_tokens' => $plan->max_tokens,
            'resets_at' => $resetsAt,
            'is_active' => true,
        ]);

        // Also update the subscription's rate_limit_resets_at if it has hourly limits
        if ($plan->rate_limit_period_hours) {
            $user->activeSubscription?->update([
                'rate_limit_resets_at' => $resetsAt,
            ]);
        }

        \Log::info("Rate limit updated for user {$this->userId} to plan: {$plan->slug}");
    }

    /**
     * Calculate when the rate limit should reset.
     */
    protected function calculateResetTime(SubscriptionPlan $plan): ?\DateTime
    {
        if (! $plan->rate_limit_period_hours) {
            // Daily reset - next midnight
            return (new \DateTime('tomorrow'))->setTime(0, 0);
        }

        // Hourly reset - add period hours to now
        return (new \DateTime)->modify("+{$plan->rate_limit_period_hours} hours");
    }
}
