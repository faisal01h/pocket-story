<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\GameSession;
use App\Models\GameSessionStateHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GamePlayEditResponseTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_edit_model_response(): void
    {
        $user = User::factory()->create();
        $game = Game::factory()->create(['user_id' => $user->id]);
        $session = GameSession::factory()->create([
            'game_id' => $game->id,
            'user_id' => $user->id,
            'mode' => 'llm',
        ]);

        // Create a model response
        $modelResponse = GameSessionStateHistory::create([
            'game_session_id' => $session->id,
            'role' => 'model',
            'content' => 'Original AI response',
        ]);

        $newContent = 'Edited AI response with better content';

        $response = $this->actingAs($user)->post(route('games.play.edit-response', [$game->id, $session->id]), [
            'history_id' => $modelResponse->id,
            'content' => $newContent,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('game_session_state_histories', [
            'id' => $modelResponse->id,
            'content' => $newContent,
        ]);
    }

    public function test_user_cannot_edit_user_message(): void
    {
        $user = User::factory()->create();
        $game = Game::factory()->create(['user_id' => $user->id]);
        $session = GameSession::factory()->create([
            'game_id' => $game->id,
            'user_id' => $user->id,
        ]);

        // Create a user message (not model)
        $userMessage = GameSessionStateHistory::create([
            'game_session_id' => $session->id,
            'role' => 'user',
            'content' => 'User input',
        ]);

        $response = $this->actingAs($user)->post(route('games.play.edit-response', [$game->id, $session->id]), [
            'history_id' => $userMessage->id,
            'content' => 'Trying to edit user message',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors();

        // Original content should be unchanged
        $this->assertDatabaseHas('game_session_state_histories', [
            'id' => $userMessage->id,
            'content' => 'User input',
        ]);
    }

    public function test_editing_latest_response_updates_dynamic_state(): void
    {
        $user = User::factory()->create();
        $game = Game::factory()->create(['user_id' => $user->id]);
        $session = GameSession::factory()->create([
            'game_id' => $game->id,
            'user_id' => $user->id,
            'mode' => 'llm',
            'dynamic_state' => [
                'content' => 'Original dynamic content',
                'choices' => [],
            ],
        ]);

        // Create the latest model response
        $latestResponse = GameSessionStateHistory::create([
            'game_session_id' => $session->id,
            'role' => 'model',
            'content' => 'Original dynamic content',
        ]);

        $newContent = 'Updated dynamic content';

        $this->actingAs($user)->post(route('games.play.edit-response', [$game->id, $session->id]), [
            'history_id' => $latestResponse->id,
            'content' => $newContent,
        ]);

        $session->refresh();

        $this->assertEquals($newContent, $session->dynamic_state['content']);
    }

    public function test_user_cannot_edit_other_users_session_responses(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $game = Game::factory()->create(['user_id' => $owner->id]);
        $session = GameSession::factory()->create([
            'game_id' => $game->id,
            'user_id' => $owner->id,
        ]);

        $modelResponse = GameSessionStateHistory::create([
            'game_session_id' => $session->id,
            'role' => 'model',
            'content' => 'Original content',
        ]);

        $response = $this->actingAs($otherUser)->post(route('games.play.edit-response', [$game->id, $session->id]), [
            'history_id' => $modelResponse->id,
            'content' => 'Trying to edit',
        ]);

        $response->assertForbidden();

        // Content should remain unchanged
        $this->assertDatabaseHas('game_session_state_histories', [
            'id' => $modelResponse->id,
            'content' => 'Original content',
        ]);
    }

    public function test_edit_requires_content(): void
    {
        $user = User::factory()->create();
        $game = Game::factory()->create(['user_id' => $user->id]);
        $session = GameSession::factory()->create([
            'game_id' => $game->id,
            'user_id' => $user->id,
        ]);

        $modelResponse = GameSessionStateHistory::create([
            'game_session_id' => $session->id,
            'role' => 'model',
            'content' => 'Original content',
        ]);

        $response = $this->actingAs($user)->post(route('games.play.edit-response', [$game->id, $session->id]), [
            'history_id' => $modelResponse->id,
            'content' => '',
        ]);

        $response->assertSessionHasErrors('content');
    }

    public function test_editing_old_response_does_not_update_dynamic_state(): void
    {
        $user = User::factory()->create();
        $game = Game::factory()->create(['user_id' => $user->id]);
        $session = GameSession::factory()->create([
            'game_id' => $game->id,
            'user_id' => $user->id,
            'mode' => 'llm',
            'dynamic_state' => [
                'content' => 'Latest response content',
                'choices' => [],
            ],
        ]);

        // Create an old response
        $oldResponse = GameSessionStateHistory::create([
            'game_session_id' => $session->id,
            'role' => 'model',
            'content' => 'Old response',
        ]);

        // Create a newer response (this is the latest)
        GameSessionStateHistory::create([
            'game_session_id' => $session->id,
            'role' => 'model',
            'content' => 'Latest response content',
        ]);

        // Edit the old response
        $this->actingAs($user)->post(route('games.play.edit-response', [$game->id, $session->id]), [
            'history_id' => $oldResponse->id,
            'content' => 'Edited old response',
        ]);

        $session->refresh();

        // Dynamic state should still have the latest response content
        $this->assertEquals('Latest response content', $session->dynamic_state['content']);

        // But the old response should be updated
        $this->assertDatabaseHas('game_session_state_histories', [
            'id' => $oldResponse->id,
            'content' => 'Edited old response',
        ]);
    }
}
