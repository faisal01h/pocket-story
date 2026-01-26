<?php

namespace App\Services;

use App\Models\GameSession;
use App\Models\LlmModel;
use Exception;
use Illuminate\Support\Facades\Http;

class VertexAIService extends LlmService
{
    public function __construct(?string $modelIdentifier = 'gemini-1.5-flash')
    {
        if ($modelIdentifier) {
            $this->llmModel = LlmModel::where('identifier', $modelIdentifier)
                ->whereHas('provider', function ($q) {
                    $q->where('slug', 'vertex-ai');
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
                    $q->where('slug', 'vertex-ai');
                })
                ->first();
        }

        if (!$this->llmModel) {
            $this->llmModel = LlmModel::where('identifier', 'gemini-2.5-flash')
                ->whereHas('provider', function ($q) {
                    $q->where('slug', 'vertex-ai');
                })
                ->first();
        }

        if (!$this->llmModel) {
            throw new Exception('Vertex AI model configuration is missing.');
        }

        if ($userId) {
            $this->checkLimits($userId, $this->llmModel->id);
        }

        // Vertex AI typically uses Google Cloud credentials and a specific endpoint structure
        // This is a simplified version assuming the endpoint_url contains the full URL with access token or proxy
        $url = $this->llmModel->endpoint_url ?: $this->llmModel->provider->base_url;
        $apiKey = $this->llmModel->provider->api_key;

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
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'temperature' => 0.95,
            ],
        ]);

        if ($response->failed()) {
            throw new Exception('Vertex AI API Failed: ' . $response->body());
        }

        $result = $response->json();
        $responseBody = $result['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if ($userId && $gameSessionId) {
            $this->logRequest(
                $userId,
                $gameSessionId,
                $this->llmModel->id,
                $prompt,
                $responseBody,
                $result['usageMetadata']['promptTokenCount'] ?? 0,
                $result['usageMetadata']['candidatesTokenCount'] ?? 0
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
        $url = $this->llmModel->endpoint_url ?: $this->llmModel->provider->base_url;

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])
        ->timeout(90)
        ->post($url, [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'responseMimeType' => $jsonMode ? 'application/json' : 'text/plain',
                'temperature' => 0.5,
            ],
        ]);

        if ($response->failed()) {
            throw new Exception('Vertex AI API Failed: ' . $response->body());
        }

        return $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? '';
    }

    public function summarizeUserActivity(
        string $userDataJson,
        ?string $model = null,
        ?int $userId = null
    ): string {
        if ($model) {
            $this->llmModel = LlmModel::where('identifier', $model)
                ->whereHas('provider', function ($q) {
                    $q->where('slug', 'vertex-ai');
                })
                ->first();
        }

        if (!$this->llmModel) {
            $this->llmModel = LlmModel::where('identifier', 'gemini-1.5-pro')
                ->whereHas('provider', function ($q) {
                    $q->where('slug', 'vertex-ai');
                })
                ->first();
        }

        if (!$this->llmModel) {
            throw new Exception('Vertex AI model configuration is missing.');
        }

        if ($userId) {
            $this->checkLimits($userId, $this->llmModel->id);
        }

        $url = $this->llmModel->endpoint_url ?: $this->llmModel->provider->base_url;
        $apiKey = $this->llmModel->provider->api_key;

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
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.7,
            ],
        ]);

        if ($response->failed()) {
            throw new Exception('Vertex AI API Failed: ' . $response->body());
        }

        $result = $response->json();
        return $result['candidates'][0]['content']['parts'][0]['text'] ?? 'Summary could not be generated.';
    }
}
