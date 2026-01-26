<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class LlmProviderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $providers = [
            [
                'name' => 'Google AI Studio',
                'slug' => 'google-ai-studio',
                'base_url' => 'https://generativelanguage.googleapis.com/v1beta/',
                'api_key' => config('services.google.ai_key'),
                'models' => [
                    [
                        'name' => 'Gemini 2.5 Flash',
                        'identifier' => 'gemini-2.5-flash',
                        'endpoint_url' => 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent',
                    ],
                    [
                        'name' => 'Gemini 2.5 Pro',
                        'identifier' => 'gemini-2.5-pro',
                        'endpoint_url' => 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-pro:generateContent',
                    ],
                ],
            ],
            [
                'name' => 'Anthropic',
                'slug' => 'anthropic',
                'base_url' => 'https://api.anthropic.com/v1/',
                'models' => [
                    [
                        'name' => 'Claude 3.5 Sonnet',
                        'identifier' => 'claude-3-5-sonnet-20240620',
                    ],
                ],
            ],
            [
                'name' => 'OpenAI',
                'slug' => 'openai',
                'base_url' => 'https://api.openai.com/v1/',
                'models' => [
                    [
                        'name' => 'GPT-4o',
                        'identifier' => 'gpt-4o',
                    ],
                ],
            ],
            [
                'name' => 'Ollama',
                'slug' => 'ollama',
                'base_url' => 'http://localhost:11434/api/',
                'models' => [
                    [
                        'name' => 'Llama 3',
                        'identifier' => 'llama3',
                    ],
                ],
            ],
            [
                'name' => 'Vertex AI',
                'slug' => 'vertex-ai',
                'base_url' => 'https://{region}-aiplatform.googleapis.com/v1/projects/{project}/locations/{region}/publishers/google/models/{model}:generateContent',
                'models' => [
                    [
                        'name' => 'Gemini 1.5 Flash (Vertex)',
                        'identifier' => 'gemini-1.5-flash',
                    ],
                    [
                        'name' => 'Gemini 1.5 Pro (Vertex)',
                        'identifier' => 'gemini-1.5-pro',
                    ],
                ],
            ],
        ];

        foreach ($providers as $pData) {
            $models = $pData['models'] ?? [];
            unset($pData['models']);

            $provider = \App\Models\LlmProvider::updateOrCreate(['slug' => $pData['slug']], $pData);

            foreach ($models as $mData) {
                $provider->models()->updateOrCreate(['identifier' => $mData['identifier']], $mData);
            }
        }
    }
}
