<?php

namespace App\Services;

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

        if (! $this->llmModel) {
            // Fallback or handle missing config
            // In a real app, you might want to throw an exception or log a warning
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
        $session = $gameSessionId ? GameSession::with(['memories', 'knowledges'])->find($gameSessionId) : null;
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
            throw new Exception('Google AI API Failed: '.$response->body());
        }

        $result = $response->json();

        if ($userId && $gameSessionId) {
            $responseBody = $result['candidates'][0]['content']['parts'][0]['text'] ?? null;

            $this->logRequest(
                $userId,
                $gameSessionId,
                $this->llmModel->id,
                $prompt,
                $responseBody,
                $result['usageMetadata']['promptTokenCount'] ?? 0,
                $result['usageMetadata']['candidatesTokenCount'] ?? 0
            );

            // Periodically summarize or check for new memories
            // (In a real app, this might be queued or handled after response)
            if ($responseBody) {
                $this->extractMemories($session, $responseBody);

                // If history is too long, we might need to summarize
                // This is a simplified check: every 10 messages? Or token count?
                $historyCount = $session->stateHistories()->count();
                if ($historyCount % 10 === 0 && $historyCount > 0) {
                    $this->summarizeHistory($session);
                }
            }
        }

        return $result;
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
     * Summarize the session history to stay within context limits.
     */
    public function summarizeHistory(GameSession $session): void
    {
        $history = $session->stateHistories()->oldest()->get();
        if ($history->isEmpty()) {
            return;
        }

        $historyStr = $history->map(fn ($h) => "{$h->role}: {$h->content}")->implode("\n");
        $currentSummary = $session->history_summary ?: 'None';

        $prompt = <<<EOT
Update the "Current History Summary" based on the "New Activity".
Maintain a concise but detailed chronological summary of key plot points, character status, and established facts.

Current History Summary:
$currentSummary

New Activity (Recent History):
$historyStr

Output the updated summary in plain text.
EOT;

        try {
            $response = Http::withHeaders(['Content-Type' => 'application/json'])
                ->post($this->llmModel->provider->base_url."?key=".$this->llmModel->provider->api_key, [
                    'contents' => [['parts' => [['text' => $prompt]]]],
                    'generationConfig' => ['temperature' => 0.5],
                ]);

            if ($response->successful()) {
                $result = $response->json();
                $newSummary = $result['candidates'][0]['content']['parts'][0]['text'] ?? null;
                if ($newSummary) {
                    $session->update(['history_summary' => trim($newSummary)]);
                }
            }
        } catch (Exception $e) {
            \Illuminate\Support\Facades\Log::error('Summarization failed: '.$e->getMessage());
        }
    }

    /**
     * Extract key details/memories from the AI's response.
     */
    protected function extractMemories(GameSession $session, string $responseBody): void
    {
        $prompt = <<<EOT
Identify any persistent facts, character relationship changes, or key inventory items mentioned in the following game segment.
Output a JSON list of short strings (e.g., ["Hero found a rusted key", "The King is angry"]).
If nothing significant changed, output [].

Game Segment:
$responseBody

JSON Output Structure:
["detail 1", "detail 2"]
EOT;

        try {
            $response = Http::withHeaders(['Content-Type' => 'application/json'])
                ->post($this->llmModel->provider->base_url."?key=".$this->llmModel->provider->api_key, [
                    'contents' => [['parts' => [['text' => $prompt]]]],
                    'generationConfig' => [
                        'responseMimeType' => 'application/json',
                        'temperature' => 0.1
                    ],
                ]);

            if ($response->successful()) {
                $result = $response->json();
                $text = $result['candidates'][0]['content']['parts'][0]['text'] ?? '[]';
                $details = json_decode($text, true);

                if (is_array($details)) {
                    foreach ($details as $detail) {
                        $session->memories()->create(['content' => $detail]);
                    }
                }
            }
        } catch (Exception $e) {
            \Illuminate\Support\Facades\Log::error('Memory extraction failed: '.$e->getMessage());
        }
    }

    /**
     * Simple keyword-based RAG to retrieve relevant session context.
     */
    protected function retrieveContext(GameSession $session, string $userAction): string
    {
        $keywords = explode(' ', strtolower($userAction));
        $context = [];

        // Retrieve relevant memories
        $memories = $session->memories()
            ->where(function ($query) use ($keywords) {
                foreach ($keywords as $word) {
                    if (strlen($word) > 3) {
                        $query->orWhere('content', 'like', "%{$word}%");
                    }
                }
            })
            ->latest()
            ->take(5)
            ->get();

        foreach ($memories as $m) {
            $context[] = "- Fact: {$m->content}";
        }

        // Retrieve relevant knowledge
        $knowledges = $session->knowledges()
            ->where(function ($query) use ($keywords) {
                foreach ($keywords as $word) {
                    if (strlen($word) > 3) {
                        $query->orWhere('content', 'like', "%{$word}%")
                              ->orWhere('key', 'like', "%{$word}%");
                    }
                }
            })
            ->get();

        foreach ($knowledges as $k) {
            $context[] = "- Knowledge ({$k->key}): {$k->content}";
        }

        // Fallback to most recent memories if nothing matches
        if (empty($context)) {
            $recent = $session->memories()->latest()->take(3)->get();
            foreach ($recent as $m) {
                $context[] = "- Fact: {$m->content}";
            }
        }

        return implode("\n", $context);
    }

    /**
     * Construct the prompt for the AI model.
     */
    protected function constructPrompt(
        string $history,
        string $userAction,
        string $contextNodesJson,
        ?string $systemInstruction = null,
        ?string $historySummary = null,
        ?string $retrievedContext = null
    ): string {
        $systemPart = $systemInstruction ? "System Guidelines:\n$systemInstruction\n\n" : '';
        $summaryPart = $historySummary ? "Past History Summary:\n$historySummary\n\n" : '';
        $contextPart = $retrievedContext ? "Key Context/Facts:\n$retrievedContext\n\n" : '';

        return <<<EOT
You are a Game Master for a text-based RPG.
$systemPart
$summaryPart
$contextPart
Recent Game History (Last few interactions):
$history

User Action: "$userAction"

Context/Nearby Nodes (Game Structure) in JSON:
$contextNodesJson

Instructions:
1. Generate the next story segment based on the user's action.
2. Provide a list of 2-4 choices for the user.
   - You MAY use choices from the 'Context Nodes' if relevant.
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
}
