<?php

namespace App\Services\Nlp;

use App\Contracts\Services\NlpServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DefaultNlpService implements NlpServiceInterface
{
    public function embedContent(array $documents): void
    {
        $response = Http::withHeaders([
            'x-token' => config('prf.nlp.api_key'),
        ])->post(config('prf.nlp.base_url').'/embedding/init', [
            'texts' => $documents,
        ]);

        if ($response->successful()) {
            Log::info('Content embedding successful for '.count($documents).' texts.');
            Log::info('Response: ', ['response' => $response->json()]);
        } else {
            Log::error('Content embedding failed.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }
    }

    public function enquire(string $question, array $conversationHistory): array
    {
        if (empty(config('prf.nlp.api_key')) || empty(config('prf.nlp.base_url'))) {
            Log::warning('ChatBot API key or base URL is not configured.');

            return ['answer' => '', 'meta' => ['error' => 'not_configured']];
        }

        if (app()->environment('testing')) {
            Log::warning('ChatBot API is not reachable at the moment.');

            return ['answer' => '', 'meta' => ['error' => 'testing_environment']];
        }

        $response = Http::withHeaders([
            'x-token' => config('prf.nlp.api_key'),
        ])->timeout(120)->post(config('prf.nlp.base_url').'/embedding/enquire', [
            'question' => $question,
            'conversation_history' => $conversationHistory,
            'stream' => false,
        ]);

        if ($response->successful()) {
            Log::info('ChatBot API response received.', [
                'response' => $response->json(),
            ]);

            return [
                'answer' => $response->json('answer'),
                'meta' => $response->json(),
            ];
        }

        if ($response->serverError()) {
            Log::warning('ChatBot API returned server error.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException('ChatBot API returned '.$response->status().'.');
        }

        Log::error('ChatBot API request failed.', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return ['answer' => '', 'meta' => ['error' => 'request_failed']];
    }
}
