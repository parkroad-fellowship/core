<?php

namespace App\Services\SpeechToText;

use App\Contracts\Services\SpeechToTextServiceInterface;
use Illuminate\Support\Facades\Http;

class AzureSpeechService implements SpeechToTextServiceInterface
{
    public function transcribe(array $contentUrls, string $displayName): array
    {
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Ocp-Apim-Subscription-Key' => config('prf.app.azure_speech.subscription_key'),
        ])->post(
            url: 'https://'
            . config('prf.app.azure_speech.region')
            . '.api.cognitive.microsoft.com/speechtotext/v3.2/transcriptions',
            data: [
                'contentUrls' => $contentUrls,
                'locale' => 'en-US',
                'displayName' => $displayName,
                'properties' => [
                    'wordLevelTimestampsEnabled' => true,
                    'languageIdentification' => [
                        'candidateLocales' => [
                            'en-US',
                            'en-KE',
                            'en-GB',
                        ],
                    ],
                ],
            ],
        );

        if (!$response->successful()) {
            return [];
        }

        return $response->json();
    }

    public function getTranscriptionStatus(string $statusUrl): array
    {
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Ocp-Apim-Subscription-Key' => config('prf.app.azure_speech.subscription_key'),
        ])->get($statusUrl);

        if (!$response->successful()) {
            return ['status' => 'failed'];
        }

        return $response->json();
    }

    public function getTranscriptionFiles(string $filesUrl): array
    {
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Ocp-Apim-Subscription-Key' => config('prf.app.azure_speech.subscription_key'),
        ])->get($filesUrl);

        if (!$response->successful()) {
            return [];
        }

        return $response->json();
    }
}
