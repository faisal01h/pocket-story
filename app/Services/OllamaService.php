<?php

namespace App\Services;

use App\Models\GameSession;
use App\Models\LlmModel;
use Exception;
use Illuminate\Support\Facades\Http;

class OllamaService extends LlmService
{
    public function __construct(?string $modelIdentifier = 'llama3')
    {
        if ($modelIdentifier) {
            $this->llmModel = LlmModel::where('identifier', $modelIdentifier)
                ->whereHas('provider', function ($q) {
                    $q->where('slug', 'ollama');
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
                    $q->where('slug', 'ollama');
                })
                ->first();
        }

        if (!$this->llmModel) {
            $this->llmModel = LlmModel::where('identifier', 'llama3')
                ->whereHas('provider', function ($q) {
                    $q->where('slug', 'ollama');
                })
                ->first();
        }

        if (!$this->llmModel) {
            throw new Exception('Ollama model configuration is missing.');
        }

        if ($userId) {
            $this->checkLimits($userId, $this->llmModel->id);
        }

        $url = $this->llmModel->endpoint_url ?: $this->llmModel->provider->base_url . '/api/generate';

        $session = $gameSessionId ? GameSession::find($gameSessionId) : null;
        $summary = $session?->history_summary;
        $memories = $session ? $this->retrieveContext($session, $userAction) : '';

        $prompt = $this->constructPrompt($history, $userAction, $contextNodesJson, $systemInstruction, $summary, $memories);

        $response = Http::timeout(120)->post($url, [
            'model' => $this->llmModel->identifier,
            'prompt' => $prompt,
            'stream' => false,
            'format' => 'json',
            'options' => [
                'temperature' => 0.95,
            ],
        ]);

        if ($response->failed()) {
            throw new Exception('Ollama API Failed: ' . $response->body());
        }

        $result = $response->json();
        $responseBody = $result['response'] ?? null;

        if ($userId && $gameSessionId) {
            $this->logRequest(
                $userId,
                $gameSessionId,
                $this->llmModel->id,
                $prompt,
                $responseBody,
                $result['prompt_eval_count'] ?? 0,
                $result['eval_count'] ?? 0
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
        $url = $this->llmModel->endpoint_url ?: $this->llmModel->provider->base_url . '/api/generate';

        $response = Http::timeout(120)->post($url, [
            'model' => $this->llmModel->identifier,
            'prompt' => $prompt,
            'stream' => false,
            'format' => $jsonMode ? 'json' : null,
            'options' => [
                'temperature' => 0.5,
            ],
        ]);

        if ($response->failed()) {
            throw new Exception('Ollama API Failed: ' . $response->body());
        }

        return $response->json()['response'] ?? '';
    }

    public function summarizeUserActivity(
        string $userDataJson,
        ?string $model = null,
        ?int $userId = null
    ): string {
        if ($model) {
            $this->llmModel = LlmModel::where('identifier', $model)
                ->whereHas('provider', function ($q) {
                    $q->where('slug', 'ollama');
                })
                ->first();
        }

        if (!$this->llmModel) {
            $this->llmModel = LlmModel::where('identifier', 'llama3')
                ->whereHas('provider', function ($q) {
                    $q->where('slug', 'ollama');
                })
                ->first();
        }

        if (!$this->llmModel) {
            throw new Exception('Ollama model configuration is missing.');
        }

        if ($userId) {
            $this->checkLimits($userId, $this->llmModel->id);
        }

        $url = $this->llmModel->endpoint_url ?: $this->llmModel->provider->base_url . '/api/generate';

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

        $response = Http::timeout(120)->post($url, [
            'model' => $this->llmModel->identifier,
            'prompt' => $prompt,
            'stream' => false,
            'options' => [
                'temperature' => 0.7,
            ],
        ]);

        if ($response->failed()) {
            throw new Exception('Ollama API Failed: ' . $response->body());
        }

        $result = $response->json();
        return $result['response'] ?? 'Summary could not be generated.';
    }
}
