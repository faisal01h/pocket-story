<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Free',
                'slug' => 'free',
                'description' => 'Free tier with basic rate limits - perfect for trying out the platform',
                'price' => 0,
                'currency' => 'IDR',
                'billing_period' => 'monthly',
                'max_tokens' => 1000,
                'rate_limit_period_hours' => null, // null = daily reset
                'is_active' => true,
                'sort_order' => 0,
            ],
            [
                'name' => 'Wanderer',
                'slug' => 'wanderer',
                'description' => 'For casual adventurers - 15,000 tokens every 6 hours to explore your stories',
                'price' => 5000000, // IDR 50,000 in cents (smallest unit)
                'currency' => 'IDR',
                'billing_period' => 'monthly',
                'max_tokens' => 15000,
                'rate_limit_period_hours' => 6,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Explorer',
                'slug' => 'explorer',
                'description' => 'For dedicated storytellers - 50,000 tokens every 5 hours for immersive gameplay',
                'price' => 20000000, // IDR 200,000 in cents (smallest unit)
                'currency' => 'IDR',
                'billing_period' => 'monthly',
                'max_tokens' => 50000,
                'rate_limit_period_hours' => 5,
                'is_active' => true,
                'sort_order' => 2,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(
                ['slug' => $plan['slug']],
                $plan
            );
        }

        $this->command->info('Subscription plans seeded successfully!');
    }
}
