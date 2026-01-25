<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LlmLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_llm_limits(): void
    {
        $user = \App\Models\User::factory()->create();

        $response = $this->actingAs($user)->get('/admin/llm-limits');

        $response->assertStatus(200);
    }

    public function test_admin_can_create_llm_limit_with_model(): void
    {
        $user = \App\Models\User::factory()->create();
        $provider = \App\Models\LlmProvider::create([
            'name' => 'Test Provider',
            'slug' => 'test-provider',
            'is_active' => true,
        ]);
        $model = \App\Models\LlmModel::create([
            'llm_provider_id' => $provider->id,
            'name' => 'Test Model',
            'identifier' => 'test-model',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post('/admin/llm-limits', [
            'user_id' => null,
            'llm_model_id' => $model->id,
            'period' => 'daily',
            'max_tokens' => 1000,
            'is_active' => true,
        ]);

        $response->assertRedirect('/admin/llm-limits');
        $this->assertDatabaseHas('llm_limits', [
            'llm_model_id' => $model->id,
            'max_tokens' => 1000,
        ]);
    }

    public function test_admin_can_update_llm_limit(): void
    {
        $user = \App\Models\User::factory()->create();
        $limit = \App\Models\LlmLimit::create([
            'user_id' => null,
            'llm_model_id' => null,
            'period' => 'daily',
            'max_tokens' => 500,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->put("/admin/llm-limits/{$limit->id}", [
            'user_id' => null,
            'llm_model_id' => null,
            'period' => 'monthly',
            'max_tokens' => 5000,
            'is_active' => true,
        ]);

        $response->assertRedirect('/admin/llm-limits');
        $this->assertDatabaseHas('llm_limits', [
            'id' => $limit->id,
            'period' => 'monthly',
            'max_tokens' => 5000,
        ]);
    }
}
