<?php

namespace Tests\Feature;

use App\Events\LlmResponseStreaming;
use App\Models\Game;
use App\Models\GameSession;
use App\Models\LlmModel;
use App\Models\LlmProvider;
use App\Models\StoryNode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LlmStreamingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Game $game;

    protected GameSession $session;

    protected LlmModel $llmModel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        // Create LLM provider and model
        $provider = LlmProvider::create([
            'slug' => 'google-ai-studio',
            'name' => 'Google AI Studio',
            'api_key' => 'test-key',
            'base_url' => 'https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent',
        ]);

        $this->llmModel = LlmModel::create([
            'llm_provider_id' => $provider->id,
            'identifier' => 'gemini-2.5-flash',
            'name' => 'Gemini 2.5 Flash',
            'is_active' => true,
        ]);

        $this->game = Game::factory()->create([
            'user_id' => $this->user->id,
            'llm_guidelines' => 'Test guidelines',
        ]);

        $startNode = StoryNode::create([
            'game_id' => $this->game->id,
            'title' => 'Start Node',
            'content' => 'Game start',
            'is_start_node' => true,
        ]);

        $this->session = GameSession::create([
            'game_id' => $this->game->id,
            'user_id' => $this->user->id,
            'current_node_id' => $startNode->id,
            'mode' => 'llm',
        ]);
    }

    public function test_config_set_to_http_uses_synchronous_method(): void
    {
        Config::set('app.llm_communication_method', 'http');

        Event::fake();

        Http::fake([
            '*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => '{"content": "Test response", "choices": []}'],
                            ],
                        ],
                    ],
                ],
                'usageMetadata' => [
                    'promptTokenCount' => 10,
                    'candidatesTokenCount' => 20,
                ],
            ]),
        ]);

        $this->actingAs($this->user)
            ->postJson(route('api.games.play.action', [$this->game->id, $this->session->id]), [
                'action_type' => 'text',
                'input_text' => 'Test action',
            ])
            ->assertOk();

        // Verify no streaming events were dispatched
        Event::assertNotDispatched(LlmResponseStreaming::class);
    }

    public function test_config_set_to_websocket_triggers_streaming(): void
    {
        Config::set('app.llm_communication_method', 'websocket');

        Event::fake();

        Http::fake([
            '*' => Http::response("data: {\"candidates\":[{\"content\":{\"parts\":[{\"text\":\"{\\\"content\\\": \\\"Test\\\", \\\"choices\\\": []}\"}]}}],\"usageMetadata\":{\"promptTokenCount\":10,\"candidatesTokenCount\":20}}\n"),
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('api.games.play.action', [$this->game->id, $this->session->id]), [
                'action_type' => 'text',
                'input_text' => 'Test action',
            ]);

        $response->assertOk()
            ->assertJson([
                'streaming' => true,
                'session_id' => $this->session->id,
            ]);

        // Verify streaming events were dispatched
        Event::assertDispatched(LlmResponseStreaming::class);
    }

    public function test_streaming_broadcasts_on_correct_channel(): void
    {
        Config::set('app.llm_communication_method', 'websocket');

        Event::fake();

        Http::fake([
            '*' => Http::response("data: {\"candidates\":[{\"content\":{\"parts\":[{\"text\":\"{\\\"content\\\": \\\"Test\\\", \\\"choices\\\": []}\"}]}}],\"usageMetadata\":{\"promptTokenCount\":10,\"candidatesTokenCount\":20}}\n"),
        ]);

        $this->actingAs($this->user)
            ->postJson(route('api.games.play.action', [$this->game->id, $this->session->id]), [
                'action_type' => 'text',
                'input_text' => 'Test action',
            ]);

        Event::assertDispatched(function (LlmResponseStreaming $event) {
            return $event->sessionId === $this->session->id
                && $event->broadcastOn()[0]->name === "game-session.{$this->session->id}";
        });
    }

    public function test_channel_authorization_works(): void
    {
        // Test that user can access their own session channel
        $authorized = \Illuminate\Support\Facades\Broadcast::channel(
            "game-session.{$this->session->id}",
            function ($user, $sessionId) {
                $session = GameSession::find($sessionId);

                return $session && (int) $session->user_id === (int) $user->id;
            }
        );

        $result = $authorized($this->user, $this->session->id);
        $this->assertTrue((bool) $result);

        // Test that user cannot access another user's session
        $otherUser = User::factory()->create();
        $otherSession = GameSession::create([
            'game_id' => $this->game->id,
            'user_id' => $otherUser->id,
        ]);

        $result = $authorized($this->user, $otherSession->id);
        $this->assertFalse((bool) $result);
    }

    public function test_regenerate_with_streaming_enabled(): void
    {
        Config::set('app.llm_communication_method', 'websocket');

        // Create some history
        $this->session->stateHistories()->createMany([
            ['role' => 'user', 'content' => 'Hello'],
            ['role' => 'model', 'content' => 'Hi there'],
        ]);

        Event::fake();

        Http::fake([
            '*' => Http::response("data: {\"candidates\":[{\"content\":{\"parts\":[{\"text\":\"{\\\"content\\\": \\\"New response\\\", \\\"choices\\\": []}\"}]}}],\"usageMetadata\":{\"promptTokenCount\":10,\"candidatesTokenCount\":20}}\n"),
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('api.games.play.regenerate', [$this->game->id, $this->session->id]));

        $response->assertOk()
            ->assertJson([
                'streaming' => true,
                'session_id' => $this->session->id,
            ]);

        Event::assertDispatched(LlmResponseStreaming::class);
    }

    public function test_existing_gameplay_still_works_with_http(): void
    {
        Config::set('app.llm_communication_method', 'http');

        Http::fake([
            '*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => '{"content": "AI response", "choices": [{"label": "Choice 1", "target_node_id": null}]}'],
                            ],
                        ],
                    ],
                ],
                'usageMetadata' => [
                    'promptTokenCount' => 15,
                    'candidatesTokenCount' => 30,
                ],
            ]),
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('api.games.play.action', [$this->game->id, $this->session->id]), [
                'action_type' => 'text',
                'input_text' => 'Test input',
            ]);

        $response->assertOk();

        // Verify history was created
        $this->assertDatabaseHas('game_session_state_histories', [
            'game_session_id' => $this->session->id,
            'role' => 'user',
            'content' => 'Test input',
        ]);

        $this->assertDatabaseHas('game_session_state_histories', [
            'game_session_id' => $this->session->id,
            'role' => 'model',
            'content' => 'AI response',
        ]);
    }
}
