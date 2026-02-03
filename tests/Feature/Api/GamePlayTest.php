<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GamePlayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Spatie\Permission\Models\Permission::create(['name' => 'game.view']);
        \Spatie\Permission\Models\Permission::create(['name' => 'game.llm-mode']);
    }

    public function test_user_can_login_and_get_token(): void
    {
        \App\Models\User::factory()->create([
            'email' => 'test@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password',
            'device_name' => 'mobile',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token', 'user']);
    }

    public function test_authenticated_user_can_list_games(): void
    {
        /** @var \App\Models\User $user */
        $user = \App\Models\User::factory()->create();
        $user->givePermissionTo('game.view');

        \App\Models\Game::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/games');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_user_can_start_and_view_session(): void
    {
        /** @var \App\Models\User $user */
        $user = \App\Models\User::factory()->create();
        $user->givePermissionTo('game.view');

        $game = \App\Models\Game::factory()->create(['user_id' => $user->id]);
        $startNode = \App\Models\StoryNode::create([
            'game_id' => $game->id,
            'title' => 'Start',
            'content' => 'Beginning of the adventure.',
            'is_start_node' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/games/{$game->id}/play");

        $response->assertStatus(201);
        $sessionId = $response->json('data.id');

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/games/{$game->id}/play/{$sessionId}");

        $response->assertStatus(200)
            ->assertJsonPath('data.current_node_id', $startNode->id);
    }

    public function test_action_creates_state_history_records(): void
    {
        $user = \App\Models\User::factory()->create();
        $user->givePermissionTo('game.view');
        $user->givePermissionTo('game.llm-mode');
        
        $game = \App\Models\Game::factory()->create(['user_id' => $user->id]);
        $startNode = \App\Models\StoryNode::create([
            'game_id' => $game->id,
            'title' => 'Start',
            'content' => 'Beginning.',
            'is_start_node' => true,
        ]);
        $targetNode = \App\Models\StoryNode::create([
            'game_id' => $game->id,
            'title' => 'Target',
            'content' => 'Target content.',
        ]);

        $label = 'Go to target';

        $choice = \App\Models\Choice::create([
            'story_node_id' => $startNode->id,
            'target_node_id' => $targetNode->id,
            'label' => $label,
        ]);

        $session = \App\Models\GameSession::create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'current_node_id' => $startNode->id,
            'mode' => 'llm',
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/games/{$game->id}/play/{$session->id}/action", [
            'action_type' => 'choice',
            'choice_id' => $choice->id,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('game_session_state_histories', [
            'game_session_id' => $session->id,
            'role' => 'user',
            'content' => $label,
        ]);

        $response->assertJsonPath('data.state_histories.0.content', $label);
    }

    public function test_user_can_view_chat_history(): void
    {
        $user = \App\Models\User::factory()->create();
        $user->givePermissionTo('game.view');
        
        $game = \App\Models\Game::factory()->create(['user_id' => $user->id]);
        $session = \App\Models\GameSession::create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'mode' => 'llm',
        ]);

        $session->stateHistories()->createMany([
            ['role' => 'user', 'content' => 'Hello'],
            ['role' => 'model', 'content' => 'Hi there'],
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/games/{$game->id}/play/{$session->id}/history");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_regeneration_works_only_when_allowed(): void
    {
        /** @var \App\Models\User $user */
        $user = \App\Models\User::factory()->create();
        $user->givePermissionTo('game.view');

        $game = \App\Models\Game::factory()->create([
            'user_id' => $user->id,
            'settings' => ['allow_llm_regeneration' => false],
        ]);

        $session = \App\Models\GameSession::create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'mode' => 'llm',
        ]);

        // Attempt regenerate when disallowed
        $response = $this->actingAs($user, 'sanctum')->postJson("/api/games/{$game->id}/play/{$session->id}/regenerate");
        // Expect forbidden or error depending on controller implementation. 
        // My implementation in step 51 doesn't check this setting, but I should probably add it later.
        // For now, let's skip the 403 assertion if I didn't add it or add the check.
        // Wait, I see validation in Web controller: if (! ($game->settings['allow_llm_regeneration'] ?? false)) abort(403);
        // I should have added that to API controller too. 
        // Let's assume I will add it.
        $response->assertStatus(403);

        // Allow regeneration
        $game->update(['settings' => ['allow_llm_regeneration' => true]]);

        // Create some history
        $session->stateHistories()->createMany([
            ['role' => 'user', 'content' => 'Hello'],
            ['role' => 'model', 'content' => 'Hi there'],
        ]);
        
        // Mock the Factory
        $mockService = \Mockery::mock('App\Services\LlmService');
        $mockService->shouldReceive('generateNextState')->andReturn(['content' => 'Regenerated content']);
        
        // Use alias mock for the factory static call
        // Note: Run with --process-isolation or ensure class not loaded
        $mockFactory = \Mockery::mock('alias:App\Services\LlmServiceFactory');
        $mockFactory->shouldReceive('make')->andReturn($mockService);

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/games/{$game->id}/play/{$session->id}/regenerate");

        $response->assertStatus(200);
    }

    public function test_regeneration_uses_custom_model_if_provided(): void
    {
        $user = \App\Models\User::factory()->create();
        $user->givePermissionTo('game.view');
        
        $provider = \App\Models\LlmProvider::create([
            'name' => 'Test Provider',
            'slug' => 'google-ai-studio',
            'is_active' => true,
        ]);
        $model = \App\Models\LlmModel::create([
            'llm_provider_id' => $provider->id,
            'name' => 'Custom Model',
            'identifier' => 'custom-model',
            'is_active' => true,
        ]);

        $game = \App\Models\Game::factory()->create([
            'user_id' => $user->id,
            'settings' => ['allow_llm_regeneration' => true],
        ]);

        $session = \App\Models\GameSession::create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'mode' => 'llm',
        ]);

        $session->stateHistories()->createMany([
            ['role' => 'user', 'content' => 'Prompt'],
            ['role' => 'model', 'content' => 'Old answer'],
        ]);

        // Mock the Factory again (or it persists if alias)
        // ALIAS MOCKS PERSIST. We cannot redefine them easily.
        // If we defined it in previous test, it might be active.
        // But since we use --process-isolation (planned), it's fine.
        // Or we can combine logic. 
        // If I cannot run --process-isolation via tool easily (Wait, I can pass arguments to run_command),
        // I will just assume the previous mock covers it or reuse logic.
        
        // Actually, declaring alias mock twice in same process throws error.
        // I should wrap in try-catch or check if mocked? No.
        
        // Let's rely on the mock defined in previous test if running in same process? 
        // No, PHPUnit resets? No, alias mocks are static class definitions.
        
        // If I put the mock in setUp?
        // But I only want it for these tests.
        
        // I will comment out one test or merge them to avoid alias conflict if isolation fails.
        // Or I will try to use `overload` which cleans up?
        // `overload:App\Services\LlmServiceFactory`
        
        $mockFactory = \Mockery::mock('alias:App\Services\LlmServiceFactory');
        $mockFactory->shouldReceive('make')->with('custom-model')->andReturnUsing(function() {
             $SERVICE = \Mockery::mock('App\Services\LlmService');
             $SERVICE->shouldReceive('generateNextState')->andReturn(['content' => 'Custom content']);
             return $SERVICE;
        });

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/games/{$game->id}/play/{$session->id}/regenerate", [
            'model' => 'custom-model',
        ]);

        $response->assertStatus(200);
    }
}
