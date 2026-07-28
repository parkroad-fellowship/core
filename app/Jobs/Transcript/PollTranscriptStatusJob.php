<?php

namespace App\Jobs\Transcript;

use App\Contracts\Services\SpeechToTextServiceInterface;
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

    public function handle(SpeechToTextServiceInterface $stt): void
    {
        $transcript = $this->transcript;

        $responseBody = $stt->getTranscriptionStatus($transcript->transcription_status_url);

        $status = PRFTranscriptionStatus::fromValue($responseBody['status']);

        $transcript->update([
            'transcription_request_meta' => $responseBody,
            'status' => $status,
        ]);

        if ($status === PRFTranscriptionStatus::SUCCEEDED) {
            $filesUrl = $responseBody['links']['files'] ?? null;

            if ($filesUrl) {
                $contentBody = $stt->getTranscriptionFiles($filesUrl);

                if (! empty($contentBody['values'][0]['links']['contentUrl'])) {
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
        }

        if ($status === PRFTranscriptionStatus::RUNNING) {
            PollTranscriptStatusJob::dispatch($transcript)
                ->delay(now()->addMinutes(2));
        }
    }
}
