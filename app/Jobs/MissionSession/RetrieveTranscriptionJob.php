<?php

namespace App\Jobs\MissionSession;

use App\Enums\PRFTranscriptionStatus;
use App\Models\MissionSessionTranscript;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class RetrieveTranscriptionJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public MissionSessionTranscript $missionSessionTranscript,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $missionSessionTranscript = $this->missionSessionTranscript;

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Ocp-Apim-Subscription-Key' => config('prf.app.azure_speech.subscription_key'),
        ])->get($missionSessionTranscript->transcription_status_url);

        if ($response->successful()) {
            $responseBody = $response->json();

            $status = PRFTranscriptionStatus::fromValue($responseBody['status']);

            $missionSessionTranscript->update([
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

                    $missionSessionTranscript->update([
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

                    $missionSessionTranscript->update([
                        'transcription_content' => $combinedContent,
                    ]);
                }
            }

            if ($status === PRFTranscriptionStatus::RUNNING) {
                // Retry after 2 minutes if still running
                RetrieveTranscriptionJob::dispatch($missionSessionTranscript)
                    ->delay(now()->addMinutes(2));
            }
        }
    }
}
