<?php

namespace App\Services;

use App\Models\GameSession;
use App\Models\LlmModel;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIService extends LlmService
{
    public function __construct(?string $modelIdentifier = 'gpt-4o')
    {
        if ($modelIdentifier) {
            $this->llmModel = LlmModel::where('identifier', $modelIdentifier)
                ->whereHas('provider', function ($q) {
                    $q->where('slug', 'openai');
                })
                ->first();
        }
    }

    public function generateNextState(
        string $history,
        string $userAction,
        string $contextNodesJson,
        ?string $model = null,
        ?int $userId = null,
        ?int $gameSessionId = null,
        ?string $systemInstruction = null
    ): array {
        if ($model) {
            $this->llmModel = LlmModel::where('identifier', $model)
                ->whereHas('provider', function ($q) {
                    $q->where('slug', 'openai');
                })
                ->first();
        }

        if (!$this->llmModel) {
            $this->llmModel = LlmModel::where('identifier', 'gpt-4o')
                ->whereHas('provider', function ($q) {
                    $q->where('slug', 'openai');
                })
                ->first();
        }

        if (!$this->llmModel) {
            throw new Exception('OpenAI model configuration is missing.');
        }

        if ($userId) {
            $this->checkLimits($userId, $this->llmModel->id);
        }

        $apiKey = $this->llmModel->provider->api_key;
        $url = $this->llmModel->endpoint_url ?: $this->llmModel->provider->base_url . '/chat/completions';

        $session = $gameSessionId ? GameSession::find($gameSessionId) : null;
        $summary = $session?->history_summary;
        $memories = $session ? $this->retrieveContext($session, $userAction) : '';

        $prompt = $this->constructPrompt($history, $userAction, $contextNodesJson, $systemInstruction, $summary, $memories);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])
        ->timeout(90)
        ->post($url, [
            'model' => $this->llmModel->identifier,
            'messages' => [
                ['role' => 'system', 'content' => 'You are a helpful Game Master.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'response_format' => ['type' => 'json_object'],
            'temperature' => 0.95,
        ]);

        if ($response->failed()) {
            throw new Exception('OpenAI API Failed: ' . $response->body());
        }

        $result = $response->json();
        $responseBody = $result['choices'][0]['message']['content'] ?? null;

        if ($userId && $gameSessionId) {
            $this->logRequest(
                $userId,
                $gameSessionId,
                $this->llmModel->id,
                $prompt,
                $responseBody,
                $result['usage']['prompt_tokens'] ?? 0,
                $result['usage']['completion_tokens'] ?? 0
            );

            // Periodically summarize and extract long-term memory/knowledge
            if ($responseBody && $session) {
                $this->handlePeriodicTasks($session, $responseBody);
            }
        }

        return $this->parseJson($responseBody);
    }

    /**
     * Call the LLM with a raw prompt.
     */
    protected function callModel(string $prompt, bool $jsonMode = false): string
    {
        $apiKey = $this->llmModel->provider->api_key;
        $url = $this->llmModel->endpoint_url ?: $this->llmModel->provider->base_url . '/chat/completions';

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])
        ->timeout(90)
        ->post($url, [
            'model' => $this->llmModel->identifier,
            'messages' => [
                ['role' => 'system', 'content' => 'You are a helpful assistant.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'response_format' => $jsonMode ? ['type' => 'json_object'] : null,
            'temperature' => 0.5,
        ]);

        if ($response->failed()) {
            throw new Exception('OpenAI API Failed: ' . $response->body());
        }

        return $response->json()['choices'][0]['message']['content'] ?? '';
    }

    public function summarizeUserActivity(
        string $userDataJson,
        ?string $model = null,
        ?int $userId = null
    ): string {
        if ($model) {
            $this->llmModel = LlmModel::where('identifier', $model)
                ->whereHas('provider', function ($q) {
                    $q->where('slug', 'openai');
                })
                ->first();
        }

        if (!$this->llmModel) {
            $this->llmModel = LlmModel::where('identifier', 'gpt-4o')
                ->whereHas('provider', function ($q) {
                    $q->where('slug', 'openai');
                })
                ->first();
        }

        if (!$this->llmModel) {
            throw new Exception('OpenAI model configuration is missing.');
        }

        if ($userId) {
            $this->checkLimits($userId, $this->llmModel->id);
        }

        $apiKey = $this->llmModel->provider->api_key;
        $url = $this->llmModel->endpoint_url ?: $this->llmModel->provider->base_url . '/chat/completions';

        $prompt = <<<EOT
Analyze the following user gameplay data and provide a detailed "Player Persona" summary.
Identify their behavior patterns, tendencies, sexual tendencies, moral alignment based on choices,
patterns, and overall engagement style.

User Data (JSON):
$userDataJson

Your response should be a well-formatted markdown report including:
1. Player Persona Name (catchy)
2. Behavioral Analysis
3. Moral Alignment
4. Recent Highlights

Don't include any greeting or closing. Just return the markdown report.
EOT;

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])
        ->timeout(90)
        ->post($url, [
            'model' => $this->llmModel->identifier,
            'messages' => [
                ['role' => 'system', 'content' => 'You are a helpful behavioral analyst.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.7,
        ]);

        if ($response->failed()) {
            throw new Exception('OpenAI API Failed: ' . $response->body());
        }

        return $response->json()['choices'][0]['message']['content'] ?? 'Summary could not be generated.';
    }
}
