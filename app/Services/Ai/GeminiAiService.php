<?php

namespace App\Services\Ai;

use App\Contracts\Services\AiServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GeminiAiService implements AiServiceInterface
{
    public function generateContent(string $systemPrompt, string $userPrompt): array
    {
        $model = config('prf.app.gemini.model');

        $response = Http::withHeaders([
            'content-type' => 'application/json',
        ])
            ->timeout(60 * 4)
            ->withQueryParameters([
                'key' => config('prf.app.gemini.api_key'),
            ])->post(
                "https://generativelanguage.googleapis.com/v1beta/{$model}:generateContent",
                [
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [
                                [
                                    'text' => 'SYSTEM INSTRUCTION: '.$systemPrompt,
                                ],
                                [
                                    'text' => $userPrompt,
                                ],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'maxOutputTokens' => config('prf.app.gemini.max_output_tokens'),
                        'response_mime_type' => 'application/json',
                    ],
                ]
            );

        if ($response->failed()) {
            Log::error('Gemini API Error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [];
        }

        $text = $response->json()['candidates'][0]['content']['parts'][0]['text'];

        $json = Str::of($text)
            ->replace('```json', '')
            ->replace('```', '')
            ->trim();

        sleep(6);

        return json_decode($json, true) ?? [];
    }
}
