<?php

namespace App\Services;

use App\Models\LlmLimit;
use App\Models\LlmModel;
use App\Models\RemoteLlmRequest;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

abstract class LlmService
{
    /**
     * The LLM model instance.
     */
    protected ?LlmModel $llmModel = null;

    /**
     * Generate the next state of the game.
     */
    abstract public function generateNextState(
        string $history,
        string $userAction,
        string $contextNodesJson,
        ?string $model = null,
        ?int $userId = null,
        ?int $gameSessionId = null,
        ?string $systemInstruction = null
    ): array;

    /**
     * Summarize user activity and behavior.
     */
    abstract public function summarizeUserActivity(
        string $userDataJson,
        ?string $model = null,
        ?int $userId = null
    ): string;

    /**
     * Parse JSON from LLM response, handling markdown blocks.
     */
    protected function parseJson(string $text, $default = [])
    {
        $text = preg_replace('/^```json\s*/', '', $text);
        $text = preg_replace('/\s*```$/', '', $text);
        
        $data = json_decode($text, true);
        
        return is_array($data) ? $data : $default;
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

    /**
     * Simple keyword-based RAG to retrieve relevant session context.
     */
    protected function retrieveContext(\App\Models\GameSession $session, string $userAction): string
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
     * Summarize a prompt using the LLM.
     *
     * @throws Exception
     */
    public function summarizePrompt(string $prompt): array
    {
        if (! $this->llmModel) {
            throw new Exception('LLM Model not configured.');
        }

        $url = $this->llmModel->endpoint_url ?: $this->llmModel->provider->base_url;
        $apiKey = $this->llmModel->provider->api_key;

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post("{$url}?key={$apiKey}", [
            'contents' => [
                [
                    'parts' => [
                        ['text' => 'Summarize this LLM prompt without removing key information: '.$prompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
            ],
        ]);

        if ($response->failed()) {
            throw new Exception('LLM API Failed: '.$response->body());
        }

        return $response->json();
    }

    /**
     * Check if the user has reached their token limit.
     *
     * @throws Exception
     */
    protected function checkLimits(int $userId, int $llmModelId): void
    {
        $limits = LlmLimit::where('is_active', true)
            ->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)->orWhereNull('user_id');
            })
            ->where(function ($q) use ($llmModelId) {
                $q->where('llm_model_id', $llmModelId)->orWhereNull('llm_model_id');
            })
            ->get();

        foreach ($limits as $limit) {
            $query = RemoteLlmRequest::query();

            // Scope request counts by user/model matching the limit definition
            if ($limit->user_id) {
                $query->where('user_id', $userId);
            }

            if ($limit->llm_model_id) {
                $query->where('llm_model_id', $llmModelId);
            }

            if ($limit->period === 'daily') {
                $query->where('created_at', '>=', now()->startOfDay());
            } elseif ($limit->period === 'monthly') {
                $query->where('created_at', '>=', now()->startOfMonth());
            }

            $consumedTokens = (int) $query->sum(DB::raw('input_token_count + output_token_count'));

            if ($consumedTokens >= $limit->max_tokens) {
                $scope = $limit->user_id ? 'user' : 'global';
                $modelName = $limit->llmModel?->name ?: 'this model';
                $modelScope = $limit->llm_model_id ? "for model $modelName" : 'across all models';

                throw new Exception("Token limit exceeded ({$limit->period} limit of {$limit->max_tokens} tokens for this $scope $modelScope).");
            }
        }
    }

    /**
     * Log the LLM request.
     */
    protected function logRequest(
        int $userId,
        int $gameSessionId,
        int $llmModelId,
        string $prompt,
        ?string $responseContent,
        int $inputTokenCount,
        int $outputTokenCount
    ): RemoteLlmRequest {
        return RemoteLlmRequest::create([
            'user_id' => $userId,
            'game_session_id' => $gameSessionId,
            'llm_model_id' => $llmModelId,
            'input_token' => $prompt,
            'output_token' => $responseContent,
            'input_token_count' => $inputTokenCount,
            'output_token_count' => $outputTokenCount,
        ]);
    }

    /**
     * Handle periodic tasks like memory extraction, knowledge extraction, and summarization.
     */
    protected function handlePeriodicTasks(\App\Models\GameSession $session, string $responseBody): void
    {
        $historyCount = $session->stateHistories()->count();
        // Every 3 user chats (6 messages: 3 user + 3 model)
        if ($historyCount % 6 === 0 && $historyCount > 0) {
            $recentHistory = $session->stateHistories()->latest('id')->take(6)->get()->reverse();
            $historySegment = $recentHistory->map(fn($h) => "{$h->role}: {$h->content}")->implode("\n");

            $this->extractMemories($session, $historySegment);
            $this->extractKnowledge($session, $historySegment);
            $this->summarizeHistory($session);
        }
    }

    /**
     * Call the LLM with a raw prompt.
     */
    abstract protected function callModel(string $prompt, bool $jsonMode = false): string;

    /**
     * Extract key knowledge/facts from the AI's response for the Knowledge Base.
     */
    protected function extractKnowledge(\App\Models\GameSession $session, string $historySegment): void
    {
        $prompt = <<<EOT
Identify any world-building facts, location details, or character lore mentioned in the following game segment that should be stored in a Knowledge Base.
Output a JSON object where keys are the topics and values are the descriptions.
If nothing significant changed, output {}.

Game Segment:
$historySegment

JSON Output Structure:
{
  "topic": "description"
}
EOT;

        try {
            $text = $this->callModel($prompt, true);
            $knowledges = $this->parseJson($text);

            if (is_array($knowledges)) {
                foreach ($knowledges as $key => $content) {
                    $session->knowledges()->updateOrCreate(
                        ['key' => $key],
                        ['content' => $content]
                    );
                }
            }
        } catch (Exception $e) {
            \Illuminate\Support\Facades\Log::error('Knowledge extraction failed: '.$e->getMessage());
        }
    }

    /**
     * Summarize the session history to stay within context limits.
     */
    protected function summarizeHistory(\App\Models\GameSession $session): void
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
            $newSummary = $this->callModel($prompt, false);
            if ($newSummary) {
                $session->update(['history_summary' => trim($newSummary)]);
            }
        } catch (Exception $e) {
            \Illuminate\Support\Facades\Log::error('Summarization failed: '.$e->getMessage());
        }
    }

    /**
     * Extract key details/memories from the AI's response.
     */
    protected function extractMemories(\App\Models\GameSession $session, string $historySegment): void
    {
        $prompt = <<<EOT
Identify any persistent facts, character relationship changes, or key inventory items mentioned in the following game segment.
Output a JSON list of short strings (e.g., ["Hero found a rusted key", "The King is angry"]).
If nothing significant changed, output [].

Game Segment:
$historySegment

JSON Output Structure:
["detail 1", "detail 2"]
EOT;

        try {
            $text = $this->callModel($prompt, true);
            $details = $this->parseJson($text);

            if (is_array($details)) {
                foreach ($details as $detail) {
                    $session->memories()->create(['content' => $detail]);
                }
            }
        } catch (Exception $e) {
            \Illuminate\Support\Facades\Log::error('Memory extraction failed: '.$e->getMessage());
        }
    }
}
