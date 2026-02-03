<?php

namespace Tests\Feature\Api;

use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\XenditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed subscription plans if needed, or create them for tests
        SubscriptionPlan::factory()->create([
            'is_active' => true,
            'name' => 'Premium Plan',
            'price' => 50000,
            'billing_period' => 'monthly',
        ]);
    }

    public function test_can_list_subscription_plans()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson(route('api.subscriptions.index'));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'plans' => [
                    '*' => [
                        'id',
                        'name',
                        'price',
                        'billing_period',
                        'is_active',
                    ]
                ],
                'current_subscription'
            ]);
    }

    public function test_can_subscribe_successfully()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        
        $plan = SubscriptionPlan::first();

        // Mock XenditService
        $this->mock(XenditService::class, function ($mock) {
            $mock->shouldReceive('createInvoice')
                ->once()
                ->andReturn([
                    'success' => true,
                    'invoice_id' => 'INV-123',
                    'invoice_url' => 'https://checkout.xendit.co/123',
                    'expires_at' => now()->addDay()->toIso8601String(),
                ]);
        });

        $response = $this->postJson(route('api.subscriptions.store'), [
            'subscription_plan_id' => $plan->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'Subscription created successfully. Please proceed to payment.',
                'invoice_url' => 'https://checkout.xendit.co/123',
            ]);
            
        $this->assertDatabaseHas('user_subscriptions', [
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'pending',
            'xendit_invoice_id' => 'INV-123',
        ]);
    }

    public function test_cannot_subscribe_if_already_subscribed()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        
        $plan = SubscriptionPlan::first();

        // Create active subscription
        UserSubscription::factory()->create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'expires_at' => now()->addMonth(),
        ]);

        $response = $this->postJson(route('api.subscriptions.store'), [
            'subscription_plan_id' => $plan->id,
        ]);

        $response->assertStatus(403)
            ->assertJsonFragment([
                'message' => 'You already have an active subscription',
            ]);
    }
    
    public function test_handles_xendit_failure()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        
        $plan = SubscriptionPlan::first();

        // Mock XenditService failure
        $this->mock(XenditService::class, function ($mock) {
            $mock->shouldReceive('createInvoice')
                ->once()
                ->andReturn([
                    'success' => false,
                    'error' => 'Simulated Xendit Error',
                ]);
        });

        $response = $this->postJson(route('api.subscriptions.store'), [
            'subscription_plan_id' => $plan->id,
        ]);
        
        $response->assertStatus(500)
            ->assertJsonFragment([
                'message' => 'Failed to create payment invoice',
                'error' => 'Simulated Xendit Error',
            ]);
            
        // Should delete the pending subscription
        $this->assertDatabaseMissing('user_subscriptions', [
            'user_id' => $user->id,
            'status' => 'pending',
        ]);
    }
}
