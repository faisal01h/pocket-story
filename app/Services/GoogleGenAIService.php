<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class GoogleGenAIService extends LlmService
{
    protected $apiKey;

    protected $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent';

    protected const GEMINI_2_5_FLASH = 'gemini-2.5-flash';

    protected const GEMINI_2_5_PRO = 'gemini-2.5-pro';

    protected const GEMINI_3_PRO_PREVIEW = 'gemini-3-pro-preview';

    protected $model;

    protected $summarizerModel = self::GEMINI_2_5_FLASH;

    public function __construct(string $model = self::GEMINI_2_5_FLASH)
    {
        $this->apiKey = config('services.google.ai_key');
        $this->model = $model;
    }

    public function generateNextState(string $history, string $userAction, string $contextNodesJson, ?string $model = null, ?int $userId = null, ?int $gameSessionId = null, ?string $systemInstruction = null)
    {
        if (! $this->apiKey) {
            throw new \Exception('Google AI API Key is missing.');
        }

        // Summarize the history if text length is greater than 10000 characters
        // if (strlen($history) > 10000) {
        //     $history = head(head($this->summarizePrompt($history)['candidates'])['content']['parts'])['text'];
        // }

        $prompt = $this->constructPrompt($history, $userAction, $contextNodesJson, $systemInstruction);
        $modelToUse = $model ?: $this->model;

        if ($userId) {
            $this->checkLimits($userId, $modelToUse);
        }

        $baseUrl = str_replace('{model}', $modelToUse, $this->baseUrl);

        // Disable thinking steps
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])
            ->timeout(90)
            ->post("{$baseUrl}?key={$this->apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                    'candidateCount' => 1,
                    'temperature' => 0.95,
                ],
            ]);

        if ($response->failed()) {
            throw new \Exception('Google AI API Failed: '.$response->body());
        }

        $result = $response->json();

        // Log the request if user info is provided
        if ($userId && $gameSessionId) {
            // Extract response content safely
            $responseContent = null;
            if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                $responseContent = $result['candidates'][0]['content']['parts'][0]['text'];
            }

            \App\Models\RemoteLlmRequest::create([
                'user_id' => $userId,
                'game_session_id' => $gameSessionId,
                'provider' => 'google_gen_ai',
                'model_name' => $modelToUse,
                'input_token' => $prompt,
                'output_token' => $responseContent,
                'input_token_count' => $result['usageMetadata']['promptTokenCount'] ?? 0,
                'output_token_count' => $result['usageMetadata']['candidatesTokenCount'] ?? 0,
            ]);
        }

        return $result;
    }

    protected function constructPrompt($history, $userAction, $contextNodesJson, $systemInstruction = null)
    {
        $systemPart = $systemInstruction ? "System Guidelines:\n$systemInstruction\n\n" : '';

        return <<<EOT
You are a Game Master for a text-based RPG.
$systemPart
Current Game History:
$history

User Action: "$userAction"

Context/Nearby Nodes (Game Structure) in JSON:
$contextNodesJson

Instructions:
1. Generate the next story segment based on the user's action.
2. Provide a list of 2-4 choices for the user.
   - You MAY use choices from the 'Context Nodes' if relevant (e.g. moving to a known room).
   - You MAY generate new dynamic choices.
   - If a choice links to an existing node ID, include 'target_node_id'.
   - If a choice is dynamic, leave 'target_node_id' null.

Response Format (JSON):
{
  "content": "The story text...",
  "choices": [
    { "label": "Choice text", "target_node_id": 123 },
    { "label": "Dynamic choice text", "target_node_id": null }
  ]
}
EOT;
    }
    protected function checkLimits(int $userId, string $model): void
    {
        $limits = \App\Models\LlmLimit::where('is_active', true)
            ->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)->orWhereNull('user_id');
            })
            ->where(function ($q) use ($model) {
                $q->where('model_name', $model)->orWhereNull('model_name');
            })
            ->get();

        foreach ($limits as $limit) {
            $query = \App\Models\RemoteLlmRequest::query();

            if ($limit->user_id) {
                $query->where('user_id', $userId);
            }

            if ($limit->model_name) {
                $query->where('model_name', $model);
            }

            if ($limit->period === 'daily') {
                $query->where('created_at', '>=', now()->startOfDay());
            } elseif ($limit->period === 'monthly') {
                $query->where('created_at', '>=', now()->startOfMonth());
            }

            $consumedTokens = $query->sum(\DB::raw('input_token_count + output_token_count'));

            if ($consumedTokens >= $limit->max_tokens) {
                $scope = $limit->user_id ? "user" : "global";
                $modelScope = $limit->model_name ? "for model $model" : "across all models";
                throw new \Exception("Token limit exceeded ({$limit->period} limit of {$limit->max_tokens} tokens for this $scope $modelScope).");
            }
        }
    }
}
