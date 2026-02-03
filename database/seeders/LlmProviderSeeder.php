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
                    [
                        'name' => 'Gemini 3 Pro (Preview)',
                        'identifier' => 'gemini-3-pro-preview',
                        'endpoint_url' => 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3-pro-preview:generateContent',
                    ],
                    [
                        'name' => 'Gemini 3 Flash',
                        'identifier' => 'gemini-3-flash',
                        'endpoint_url' => 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3-flash:generateContent',
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
