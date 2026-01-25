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
}
