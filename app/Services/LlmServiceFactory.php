<?php

namespace App\Services;

use App\Models\LlmModel;
use Exception;

class LlmServiceFactory
{
    /**
     * Resolve the appropriate LLM service based on the provider.
     *
     * @throws Exception
     */
    public static function make(?string $modelIdentifier = null): LlmService
    {
        $llmModel = null;

        if ($modelIdentifier) {
            $llmModel = LlmModel::where('identifier', $modelIdentifier)
                ->where('is_active', true)
                ->with('provider')
                ->first();
        }

        if (!$llmModel) {
            // Fallback to default model if none specified or found
            $llmModel = LlmModel::where('is_active', true)
                ->with('provider')
                ->first();
        }

        if (!$llmModel) {
            throw new Exception('No active LLM model configured in the system.');
        }

        $providerSlug = $llmModel->provider->slug;

        return match ($providerSlug) {
            'google-ai-studio' => new GoogleGenAIService($llmModel->identifier),
            'openai' => new OpenAIService($llmModel->identifier),
            'anthropic' => new AnthropicService($llmModel->identifier),
            'ollama' => new OllamaService($llmModel->identifier),
            'vertex-ai' => new VertexAIService($llmModel->identifier),
            default => throw new Exception("Unsupported LLM provider: {$providerSlug}"),
        };
    }
}
