<?php

namespace App\Jobs\MissionSession;

use App\Enums\PRFTranscriptionStatus;
use App\Models\MissionSession;
use App\Models\MissionSessionTranscript;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ConvertToWavJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Media $media,
        public MissionSession $missionSession,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $media = $this->media;
        $missionSession = $this->missionSession;

        if (! Str::of($media->mime_type)->contains('audio')) {
            return;
        }

        // Download the file
        // Ensure the temp directory exists
        if (! file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $tempOriginalFile = storage_path('app/temp/'.basename($media->file_name));
        $processedPath = storage_path('app/temp/processed_'.basename($media->file_name).'.wav');

        Log::info('Downloading audio file to: '.$tempOriginalFile);

        // Download the file to temp location
        $this->downloadFile(
            url: $media->getUrl(),
            path: $tempOriginalFile
        );

        // Modified command to output WAV format using the downloaded temp file
        $command = "ffmpeg -i \"{$tempOriginalFile}\" -ar 16000 -ac 1 \"{$processedPath}\"";
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            Log::error('Failed to process audio file');

            return;
        }

        Log::info('Audio file processed successfully');
        Log::info("Adding media file: {$processedPath} to mission session {$missionSession->ulid}");

        set_time_limit(0); // 0 = no limit (in seconds)
        $media = $missionSession
            ->addMedia($processedPath)
            ->toMediaCollection(
                Arr::first(
                    MissionSession::MEDIA_COLLECTIONS,
                    fn ($collection) => $collection === 'session-audios'
                )
            );
        set_time_limit(30);

        Log::info('Done');

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Ocp-Apim-Subscription-Key' => config('prf.app.azure_speech.subscription_key'),
        ])->post(
            url: 'https://'.config('prf.app.azure_speech.region').'.api.cognitive.microsoft.com/speechtotext/v3.2/transcriptions',
            data: [
                'contentUrls' => [$media->getUrl()],
                'locale' => 'en-US',
                'displayName' => "Mission Session Audio: {$missionSession->ulid}",
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

        if ($response->successful()) {
            $responseBody = $response->json();
            $missionSessionTranscript = MissionSessionTranscript::create([
                'mission_session_id' => $missionSession->id,
                'media_id' => $media->id,
                'transcription_status_url' => $responseBody['self'],
                'status' => PRFTranscriptionStatus::fromValue($responseBody['status']),
                'transcription_request_meta' => $responseBody,
            ]);

            // Schedule a job to retrieve the transcription after 2 minutes
            RetrieveTranscriptionJob::dispatch($missionSessionTranscript)
                ->delay(now()->addMinutes(2));

            return;
        }
    }

    private function downloadFile(string $url, string $path): void
    {
        $response = Http::timeout(60)
            ->connectTimeout(10)
            ->withOptions(['sink' => $path])
            ->get($url);

        if (! $response->successful()) {
            throw new \RuntimeException("Failed to download file from: {$url}");
        }
    }
}
