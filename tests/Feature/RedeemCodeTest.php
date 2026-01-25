<?php

namespace Tests\Feature;

use App\Models\RedeemCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RedeemCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_redeem_valid_code(): void
    {
        $user = User::factory()->create();
        $code = RedeemCode::create([
            'code' => 'TEST-CODE-0001',
            'max_tokens' => 100000,
            'period' => 'total',
            'usage_limit' => 10,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post('/redeem', [
            'code' => 'TEST-CODE-0001',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('redeem_code_usages', [
            'user_id' => $user->id,
            'redeem_code_id' => $code->id,
        ]);

        $this->assertDatabaseHas('llm_limits', [
            'user_id' => $user->id,
            'max_tokens' => 100000,
            'period' => 'total',
        ]);
    }

    public function test_user_cannot_redeem_same_code_twice(): void
    {
        $user = User::factory()->create();
        $code = RedeemCode::create([
            'code' => 'DOUBLE-USE',
            'max_tokens' => 50000,
            'is_active' => true,
        ]);

        $this->actingAs($user)->post('/redeem', ['code' => 'DOUBLE-USE']);

        $response = $this->actingAs($user)->post('/redeem', ['code' => 'DOUBLE-USE']);

        $response->assertSessionHasErrors(['code']);
        $this->assertEquals(1, $code->usages()->count());
    }

    public function test_user_cannot_redeem_expired_code(): void
    {
        $user = User::factory()->create();
        $code = RedeemCode::create([
            'code' => 'EXPIRED',
            'max_tokens' => 50000,
            'expires_at' => now()->subDay(),
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post('/redeem', ['code' => 'EXPIRED']);

        $response->assertStatus(404); // firstOrFail
    }

    public function test_user_cannot_redeem_fully_used_code(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $code = RedeemCode::create([
            'code' => 'ONE-TIME',
            'max_tokens' => 50000,
            'usage_limit' => 1,
            'is_active' => true,
        ]);

        $this->actingAs($user1)->post('/redeem', ['code' => 'ONE-TIME']);

        $response = $this->actingAs($user2)->post('/redeem', ['code' => 'ONE-TIME']);

        $response->assertSessionHasErrors(['code']);
    }
}
