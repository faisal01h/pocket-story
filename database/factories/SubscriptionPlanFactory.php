<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SubscriptionPlan>
 */
class SubscriptionPlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'slug' => $this->faker->unique()->slug(),
            'description' => $this->faker->sentence(),
            'price' => $this->faker->numberBetween(0, 500000),
            'currency' => 'IDR',
            'billing_period' => $this->faker->randomElement(['weekly', 'monthly', 'yearly']),
            'max_tokens' => $this->faker->numberBetween(1000, 100000),
            'rate_limit_period_hours' => null, // null for daily reset
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    /**
     * Indicate that the plan is a free tier.
     */
    public function free(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Free',
            'slug' => 'free',
            'description' => 'Free tier with basic rate limits',
            'price' => 0,
            'max_tokens' => 1000,
            'rate_limit_period_hours' => null,
            'billing_period' => 'monthly',
            'sort_order' => 0,
        ]);
    }

    /**
     * Indicate that the plan is hourly-based.
     */
    public function hourly(int $hours = 6): static
    {
        return $this->state(fn (array $attributes) => [
            'rate_limit_period_hours' => $hours,
        ]);
    }

    /**
     * Indicate that the plan is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
