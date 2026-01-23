<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

abstract class LlmService
{
    public function summarizePrompt(string $prompt)
    {
        $baseUrl = str_replace('{model}', $this->summarizerModel, $this->baseUrl);

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post("{$baseUrl}?key={$this->apiKey}", [
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
            throw new \Exception('Google AI API Failed: '.$response->body());
        }

        return $response->json();
    }
}
