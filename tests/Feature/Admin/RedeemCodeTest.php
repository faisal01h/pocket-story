<?php

namespace Tests\Feature\Admin;

use App\Models\RedeemCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RedeemCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_redeem_codes(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/admin/redeem-codes');
        $response->assertStatus(200);
    }

    public function test_admin_can_create_redeem_code(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->post('/admin/redeem-codes', [
            'code' => 'ADMIN-CREATED',
            'max_tokens' => 200000,
            'period' => 'monthly',
            'usage_limit' => 100,
            'is_active' => true,
        ]);

        $response->assertRedirect('/admin/redeem-codes');
        $this->assertDatabaseHas('redeem_codes', ['code' => 'ADMIN-CREATED']);
    }

    public function test_admin_can_update_redeem_code(): void
    {
        $user = User::factory()->create();
        $code = RedeemCode::create([
            'code' => 'OLD-CODE',
            'max_tokens' => 1000,
            'usage_limit' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->put("/admin/redeem-codes/{$code->id}", [
            'code' => 'NEW-CODE',
            'max_tokens' => 2000,
            'period' => 'daily',
            'usage_limit' => 2,
            'is_active' => false,
        ]);

        $response->assertRedirect('/admin/redeem-codes');
        $this->assertDatabaseHas('redeem_codes', [
            'id' => $code->id,
            'code' => 'NEW-CODE',
            'is_active' => false,
        ]);
    }

    public function test_admin_can_delete_redeem_code(): void
    {
        $user = User::factory()->create();
        $code = RedeemCode::create([
            'code' => 'DELETE-ME',
            'max_tokens' => 1000,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->delete("/admin/redeem-codes/{$code->id}");

        $response->assertRedirect('/admin/redeem-codes');
        $this->assertDatabaseMissing('redeem_codes', ['id' => $code->id]);
    }
}
