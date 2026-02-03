<?php

namespace Database\Factories;

use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserSubscription>
 */
class UserSubscriptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = $this->faker->dateTimeBetween('-1 month', 'now');

        return [
            'user_id' => User::factory(),
            'subscription_plan_id' => SubscriptionPlan::factory(),
            'status' => 'active',
            'starts_at' => $startsAt,
            'expires_at' => (clone $startsAt)->modify('+1 month'),
            'cancelled_at' => null,
            'rate_limit_resets_at' => null,
            'xendit_invoice_id' => 'inv_'.$this->faker->uuid(),
            'xendit_customer_id' => 'cust_'.$this->faker->uuid(),
        ];
    }

    /**
     * Indicate that the subscription is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'starts_at' => now()->subDays(7),
            'expires_at' => now()->addDays(23),
            'cancelled_at' => null,
        ]);
    }

    /**
     * Indicate that the subscription is pending payment.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'starts_at' => null,
            'expires_at' => null,
            'cancelled_at' => null,
        ]);
    }

    /**
     * Indicate that the subscription is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
            'starts_at' => now()->subDays(60),
            'expires_at' => now()->subDays(30),
            'cancelled_at' => null,
        ]);
    }

    /**
     * Indicate that the subscription is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'starts_at' => now()->subDays(30),
            'expires_at' => now()->addDays(30),
            'cancelled_at' => now()->subDays(15),
        ]);
    }

    /**
     * Indicate that the subscription has hourly rate limits.
     */
    public function withHourlyRateLimit(int $hours = 6): static
    {
        return $this->state(fn (array $attributes) => [
            'rate_limit_resets_at' => now()->addHours($hours),
        ]);
    }
}
