<?php

namespace App\Jobs\Transcript;

use App\Contracts\Services\SpeechToTextServiceInterface;
use App\Enums\PRFMorphType;
use App\Enums\PRFTranscriptionStatus;
use App\Models\MissionQuestion;
use App\Models\MissionSession;
use App\Models\PRFEvent;
use App\Models\Transcript;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProcessAudioTranscriptJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Media $media,
        public MissionSession|MissionQuestion|PRFEvent $transcriptable,
    ) {}

    public function handle(SpeechToTextServiceInterface $stt): void
    {
        $media = $this->media;
        $transcriptable = $this->transcriptable;

        if (!Str::of($media->mime_type)->contains('audio')) {
            return;
        }

        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $tempOriginalFile = storage_path('app/temp/' . basename($media->file_name));
        $processedPath = storage_path('app/temp/processed_' . basename($media->file_name) . '.wav');

        Log::info('Downloading audio file to: ' . $tempOriginalFile);

        $this->downloadFile(url: $media->getUrl(), path: $tempOriginalFile);

        $command = "ffmpeg -i \"{$tempOriginalFile}\" -ar 16000 -ac 1 \"{$processedPath}\"";
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            Log::error('Failed to process audio file');

            return;
        }

        Log::info('Audio file processed successfully');
        Log::info('Adding processed audio file', [
            'processed_path' => $processedPath,
            'transcriptable_model' => $transcriptable::class,
            'transcriptable_ulid' => $transcriptable->ulid,
        ]);

        set_time_limit(0);
        $media = $transcriptable
            ->addMedia($processedPath)
            ->toMediaCollection($this->resolveTargetCollection($transcriptable));
        set_time_limit(30);

        $responseBody = $stt->transcribe(
            contentUrls: [$media->getUrl()],
            displayName: "{$this->resolveTranscriptableName($transcriptable)} Audio: {$transcriptable->ulid}",
        );

        if (empty($responseBody)) {
            return;
        }

        $transcript = Transcript::create([
            'mission_session_id' => $transcriptable instanceof MissionSession ? $transcriptable->id : null,
            'transcriptable_id' => $transcriptable->id,
            'transcriptable_type' => $this->resolveMorphType($transcriptable),
            'media_id' => $media->id,
            'transcription_status_url' => $responseBody['self'],
            'status' => PRFTranscriptionStatus::fromValue($responseBody['status']),
            'transcription_request_meta' => $responseBody,
        ]);

        PollTranscriptStatusJob::dispatch($transcript)->delay(now()->addMinutes(2));
    }

    private function resolveMorphType(MissionSession|MissionQuestion|PRFEvent $transcriptable): PRFMorphType
    {
        return match ($transcriptable::class) {
            MissionSession::class => PRFMorphType::MISSION_SESSION,
            MissionQuestion::class => PRFMorphType::MISSION_QUESTION,
            PRFEvent::class => PRFMorphType::EVENT,
        };
    }

    private function resolveTranscriptableName(MissionSession|MissionQuestion|PRFEvent $transcriptable): string
    {
        return match ($transcriptable::class) {
            MissionSession::class => 'Mission Session',
            MissionQuestion::class => 'Mission Question',
            PRFEvent::class => 'Event',
        };
    }

    private function resolveTargetCollection(MissionSession|MissionQuestion|PRFEvent $transcriptable): string
    {
        return match ($transcriptable::class) {
            MissionSession::class => MissionSession::SESSION_AUDIOS,
            MissionQuestion::class => MissionQuestion::QUESTION_ANSWERS,
            PRFEvent::class => PRFEvent::EVENT_RECORDINGS,
        };
    }

    private function downloadFile(string $url, string $path): void
    {
        $response = Http::timeout(60)->connectTimeout(10)->withOptions(['sink' => $path])->get($url);

        if (!$response->successful()) {
            throw new \RuntimeException("Failed to download file from: {$url}");
        }
    }
}
