<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GamePlayTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_and_get_token(): void
    {
        $user = \App\Models\User::factory()->create([
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
        $user = \App\Models\User::factory()->create();
        \App\Models\Game::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/games');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_user_can_start_and_view_session(): void
    {
        $user = \App\Models\User::factory()->create();
        $game = \App\Models\Game::factory()->create(['user_id' => $user->id]);
        $startNode = \App\Models\StoryNode::create([
            'game_id' => $game->id,
            'title' => 'Start',
            'content' => 'Beginning of the adventure.',
            'is_start_node' => true,
        ]);

        // Note: Weyfinder might not work perfectly in tests for API routes like this,
        // using manual URL for now to be safe.
        $response = $this->actingAs($user, 'sanctum')->postJson("/api/games/{$game->id}/play");

        $response->assertStatus(201);
        $sessionId = $response->json('data.id');

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/games/{$game->id}/play/{$sessionId}");

        $response->assertStatus(200)
            ->assertJsonPath('data.current_node_id', $startNode->id);
    }
}
