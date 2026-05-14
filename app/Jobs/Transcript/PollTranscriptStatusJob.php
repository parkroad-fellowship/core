<?php

namespace App\Jobs\Transcript;

use App\Enums\PRFTranscriptionStatus;
use App\Models\Transcript;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class PollTranscriptStatusJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Transcript $transcript,
    ) {}

    public function handle(): void
    {
        $transcript = $this->transcript;

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Ocp-Apim-Subscription-Key' => config('prf.app.azure_speech.subscription_key'),
        ])->get($transcript->transcription_status_url);

        if (! $response->successful()) {
            return;
        }

        $responseBody = $response->json();

        $status = PRFTranscriptionStatus::fromValue($responseBody['status']);

        $transcript->update([
            'transcription_request_meta' => $responseBody,
            'status' => $status,
        ]);

        if ($status === PRFTranscriptionStatus::SUCCEEDED) {
            $content = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Ocp-Apim-Subscription-Key' => config('prf.app.azure_speech.subscription_key'),
            ])->get($responseBody['links']['files']);

            if ($content->successful()) {
                $contentBody = $content->json();

                $contentUrl = $contentBody['values'][0]['links']['contentUrl'];

                $transcript->update([
                    'transcription_meta' => $contentBody,
                    'transcription_content_url' => $contentUrl,
                ]);

                $transcription = Http::get($contentUrl);

                $combinedContent = '';
                foreach ($transcription->json()['recognizedPhrases'] as $phrase) {
                    foreach ($phrase['nBest'] as $nBest) {
                        $combinedContent .= $nBest['display'].PHP_EOL;
                    }
                }

                $transcript->update([
                    'transcription_content' => $combinedContent,
                ]);
            }
        }

        if ($status === PRFTranscriptionStatus::RUNNING) {
            PollTranscriptStatusJob::dispatch($transcript)
                ->delay(now()->addMinutes(2));
        }
    }
}
