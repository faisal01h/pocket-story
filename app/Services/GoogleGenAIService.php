<?php

namespace App\Services;

use App\Events\LlmResponseStreaming;
use App\Models\GameSession;
use App\Models\LlmModel;
use Exception;
use Illuminate\Support\Facades\Http;

class GoogleGenAIService extends LlmService
{
    /**
     * Create a new GoogleGenAIService instance.
     */
    public function __construct(?string $modelIdentifier = 'gemini-2.5-flash')
    {
        if ($modelIdentifier) {
            $this->llmModel = LlmModel::where('identifier', $modelIdentifier)
                ->whereHas('provider', function ($q) {
                    $q->where('slug', 'google-ai-studio');
                })
                ->first();
        }
    }

    /**
     * Generate the next state of the game based on history and user action.
     *
     * @throws Exception
     */
    public function generateNextState(
        string $history,
        string $userAction,
        string $contextNodesJson,
        ?string $model = null,
        ?int $userId = null,
        ?int $gameSessionId = null,
        ?string $systemInstruction = null
    ): array {
        // Check if streaming is enabled via configuration
        if (config('app.llm_communication_method') === 'websocket' && $gameSessionId) {
            return $this->generateNextStateStreaming(
                $history,
                $userAction,
                $contextNodesJson,
                $model,
                $userId,
                $gameSessionId,
                $systemInstruction
            );
        }

        // Continue with HTTP-based implementation
        // Resolve model if identifier passed
        if ($model) {
            $this->llmModel = LlmModel::where('identifier', $model)
                ->whereHas('provider', function ($q) {
                    $q->where('slug', 'google-ai-studio');
                })
                ->first();
        }

        // Ensure we have a model
        if (! $this->llmModel) {
            $this->llmModel = LlmModel::where('identifier', 'gemini-2.5-flash')
                ->whereHas('provider', function ($q) {
                    $q->where('slug', 'google-ai-studio');
                })
                ->first();
        }

        if (! $this->llmModel) {
            throw new Exception('Google AI model configuration is missing in database.');
        }

        $modelIdentifier = $this->llmModel->identifier;

        if ($userId) {
            $this->checkLimits($userId, $this->llmModel->id);
        }

        $apiKey = $this->llmModel->provider->api_key;
        $url = $this->llmModel->endpoint_url ?: str_replace('{model}', $modelIdentifier, $this->llmModel->provider->base_url);

        // Fetch session data if available for RAG and Summary
        $session = $gameSessionId ? GameSession::find($gameSessionId) : null;
        $summary = $session?->history_summary;
        $memories = $session ? $this->retrieveContext($session, $userAction) : '';

        $prompt = $this->constructPrompt($history, $userAction, $contextNodesJson, $systemInstruction, $summary, $memories);

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])
            ->timeout(90)
            ->post("{$url}?key={$apiKey}", [
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
            throw new Exception('Google AI API Failed: '.($response->body() ?: 'Empty response'));
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

        return $this->parseJson($responseBody ?? '{}');
    }

    /**
     * Summarize user activity and behavior.
     *
     * @throws Exception
     */
    public function summarizeUserActivity(
        string $userDataJson,
        ?string $model = null,
        ?int $userId = null
    ): string {
        // Resolve model if identifier passed
        if ($model) {
            $this->llmModel = LlmModel::where('identifier', $model)
                ->whereHas('provider', function ($q) {
                    $q->where('slug', 'google-ai-studio');
                })
                ->first();
        }

        // Ensure we have a model (fallback to default if necessary/possible)
        if (! $this->llmModel) {
            $this->llmModel = LlmModel::where('identifier', 'gemini-2.5-pro') // Use Pro for summarization
                ->whereHas('provider', function ($q) {
                    $q->where('slug', 'google-ai-studio');
                })
                ->first();
        }

        if (! $this->llmModel) {
            throw new Exception('Google AI model configuration is missing in database.');
        }

        $modelIdentifier = $this->llmModel->identifier;

        if ($userId) {
            $this->checkLimits($userId, $this->llmModel->id);
        }

        $apiKey = $this->llmModel->provider->api_key;
        $url = $this->llmModel->endpoint_url ?: str_replace('{model}', $modelIdentifier, $this->llmModel->provider->base_url);

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
            'Content-Type' => 'application/json',
        ])
            ->timeout(90)
            ->post("{$url}?key={$apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'candidateCount' => 1,
                    'temperature' => 0.7,
                ],
            ]);

        if ($response->failed()) {
            throw new Exception('Google AI API Failed: '.$response->body());
        }

        return $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? 'Summary could not be generated.';
    }

    /**
     * Call the LLM with a raw prompt.
     */
    protected function callModel(string $prompt, bool $jsonMode = false): string
    {
        $apiKey = $this->llmModel->provider->api_key;
        $url = $this->llmModel->endpoint_url ?: str_replace('{model}', $this->llmModel->identifier, $this->llmModel->provider->base_url);

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])
            ->timeout(90)
            ->post("{$url}?key={$apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'responseMimeType' => $jsonMode ? 'application/json' : 'text/plain',
                    'candidateCount' => 1,
                    'temperature' => 0.5,
                ],
            ]);

        if ($response->failed()) {
            throw new Exception('Google AI API Failed: '.$response->body());
        }

        return $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? '';
    }

    /**
     * Generate the next state with streaming via WebSocket.
     *
     * @throws Exception
     */
    protected function generateNextStateStreaming(
        string $history,
        string $userAction,
        string $contextNodesJson,
        ?string $model = null,
        ?int $userId = null,
        ?int $gameSessionId = null,
        ?string $systemInstruction = null
    ): array {
        // Resolve model if identifier passed
        if ($model) {
            $this->llmModel = LlmModel::where('identifier', $model)
                ->whereHas('provider', function ($q) {
                    $q->where('slug', 'google-ai-studio');
                })
                ->first();
        }

        // Ensure we have a model
        if (! $this->llmModel) {
            $this->llmModel = LlmModel::where('identifier', 'gemini-2.5-flash')
                ->whereHas('provider', function ($q) {
                    $q->where('slug', 'google-ai-studio');
                })
                ->first();
        }

        if (! $this->llmModel) {
            throw new Exception('Google AI model configuration is missing in database.');
        }

        $modelIdentifier = $this->llmModel->identifier;

        if ($userId) {
            $this->checkLimits($userId, $this->llmModel->id);
        }

        $apiKey = $this->llmModel->provider->api_key;
        // Use streamGenerateContent endpoint for streaming
        $baseUrl = $this->llmModel->endpoint_url ?: str_replace('{model}', $modelIdentifier, $this->llmModel->provider->base_url);
        $url = str_replace(':generateContent', ':streamGenerateContent', $baseUrl);

        // Fetch session data if available for RAG and Summary
        $session = $gameSessionId ? GameSession::find($gameSessionId) : null;
        $summary = $session?->history_summary;
        $memories = $session ? $this->retrieveContext($session, $userAction) : '';

        $prompt = $this->constructPrompt($history, $userAction, $contextNodesJson, $systemInstruction, $summary, $memories);

        // Stream the response
        $fullResponseText = '';
        $inputTokenCount = 0;
        $outputTokenCount = 0;

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])
                ->timeout(90)
                ->post("{$url}?key={$apiKey}&alt=sse", [
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
                throw new Exception('Google AI API Failed: '.($response->body() ?: 'Empty response'));
            }

            // Parse the streaming response (Server-Sent Events format)
            $body = $response->body();
            $lines = explode("\n", $body);

            foreach ($lines as $line) {
                if (empty($line) || ! str_starts_with($line, 'data: ')) {
                    continue;
                }

                $data = substr($line, 6); // Remove 'data: ' prefix
                $chunk = json_decode($data, true);

                if (! $chunk) {
                    continue;
                }

                // Extract text from the chunk
                $text = $chunk['candidates'][0]['content']['parts'][0]['text'] ?? null;

                if ($text) {
                    $fullResponseText .= $text;

                    // Broadcast the token
                    event(new LlmResponseStreaming(
                        sessionId: $gameSessionId,
                        token: $text,
                        done: false
                    ));
                }

                // Extract token counts from usage metadata
                if (isset($chunk['usageMetadata'])) {
                    $inputTokenCount = $chunk['usageMetadata']['promptTokenCount'] ?? $inputTokenCount;
                    $outputTokenCount = $chunk['usageMetadata']['candidatesTokenCount'] ?? $outputTokenCount;
                }
            }

            // Send final "done" event
            event(new LlmResponseStreaming(
                sessionId: $gameSessionId,
                token: null,
                done: true,
                metadata: [
                    'input_tokens' => $inputTokenCount,
                    'output_tokens' => $outputTokenCount,
                ]
            ));

            // Parse the accumulated response
            $result = $this->parseJson($fullResponseText ?: '{}');

            if ($userId && $gameSessionId) {
                $this->logRequest(
                    $userId,
                    $gameSessionId,
                    $this->llmModel->id,
                    $prompt,
                    $fullResponseText,
                    $inputTokenCount,
                    $outputTokenCount
                );

                // Periodically summarize and extract long-term memory/knowledge
                if ($fullResponseText && $session) {
                    $this->handlePeriodicTasks($session, $fullResponseText);
                }
            }

            return $result;
        } catch (Exception $e) {
            // Broadcast error
            event(new LlmResponseStreaming(
                sessionId: $gameSessionId,
                token: null,
                done: true,
                metadata: ['error' => $e->getMessage()]
            ));

            throw $e;
        }
    }
}
