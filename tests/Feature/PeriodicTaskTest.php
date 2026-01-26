<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\GameSession;
use App\Models\LlmModel;
use App\Models\LlmProvider;
use App\Models\User;
use App\Services\GoogleGenAIService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PeriodicTaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_periodic_tasks_trigger_every_3_chats()
    {
        $user = User::factory()->create();
        $provider = LlmProvider::create([
            'name' => 'Google AI Studio',
            'slug' => 'google-ai-studio',
            'base_url' => 'https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent',
            'api_key' => 'test-key',
        ]);
        
        $model = LlmModel::create([
            'llm_provider_id' => $provider->id,
            'name' => 'Gemini 2.5 Flash',
            'identifier' => 'gemini-2.5-flash',
            'is_active' => true,
        ]);

        $game = Game::factory()->create();
        $session = GameSession::create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'mode' => 'llm',
        ]);

        // Mock AI responses
        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => json_encode(['content' => 'AI Response', 'choices' => []])]
                            ]
                        ]
                    ]
                ],
                'usageMetadata' => [
                    'promptTokenCount' => 10,
                    'candidatesTokenCount' => 10,
                ]
            ], 200),
        ]);

        $aiService = new GoogleGenAIService('gemini-2.5-flash');

        // Chat 1
        $session->stateHistories()->createMany([
            ['role' => 'user', 'content' => 'User chat 1'],
            ['role' => 'model', 'content' => 'Model response 1'],
        ]);
        
        // Chat 2
        $session->stateHistories()->createMany([
            ['role' => 'user', 'content' => 'User chat 2'],
            ['role' => 'model', 'content' => 'Model response 2'],
        ]);

        // Chat 3
        $session->stateHistories()->createMany([
            ['role' => 'user', 'content' => 'User chat 3'],
            ['role' => 'model', 'content' => 'Model response 3'],
        ]);

        // Now we have 6 messages.
        $this->assertEquals(6, $session->stateHistories()->count());

        // The next call should trigger periodic tasks
        // because historyCount % 6 === 0 and historyCount > 0
        
        // Mocking the specific extraction responses
        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent*' => Http::sequence()
                ->push(['candidates' => [['content' => ['parts' => [['text' => 'AI Response']]]]]]) // generateNextState response
                ->push(['candidates' => [['content' => ['parts' => [['text' => '["Memory 1"]']]]]]]) // extractMemories
                ->push(['candidates' => [['content' => ['parts' => [['text' => '{"Topic": "Fact"}']]]]]]) // extractKnowledge
                ->push(['candidates' => [['content' => ['parts' => [['text' => 'Updated Summary']]]]]]), // summarizeHistory
        ]);

        $aiService->generateNextState(
            'history',
            'User chat 4',
            '[]',
            'gemini-2.5-flash',
            $user->id,
            $session->id
        );

        $this->assertEquals(1, $session->memories()->count());
        $this->assertEquals(1, $session->knowledges()->count());
        $this->assertEquals('Updated Summary', $session->history_summary);
    }
}
