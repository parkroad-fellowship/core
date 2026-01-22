<?php

namespace App\Jobs\Mission;

use App\Models\Mission;
use App\Models\MissionSocialMediaPost;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Spatie\TemporaryDirectory\TemporaryDirectory;
use Throwable;

class UploadVideoToStorageJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = [30, 60, 120];

    public $timeout = 300; // 5 minutes for upload

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $missionId,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Uploading video to storage', ['mission_id' => $this->missionId]);

        $mission = Mission::with(['school'])->find($this->missionId);
        if (! $mission) {
            throw new Exception("Mission with ID {$this->missionId} not found");
        }

        $socialMediaPost = MissionSocialMediaPost::where('mission_id', $this->missionId)->first();
        if (! $socialMediaPost) {
            throw new Exception("Social media post record not found for mission {$this->missionId}");
        }

        if ($socialMediaPost->status !== 'video_created') {
            throw new Exception("Expected status 'video_created', but got '{$socialMediaPost->status}'");
        }

        $videoPath = $socialMediaPost->video_path;
        if (! $videoPath || ! file_exists($videoPath)) {
            throw new Exception('Video file does not exist: '.($videoPath ?? 'null'));
        }

        // Update status to uploading
        $socialMediaPost->updateStatus('uploading_video');

        try {
            $videoUrl = $this->uploadVideoToStorage($videoPath, $mission);

            if ($videoUrl) {
                Log::info('Video uploaded successfully', [
                    'mission_id' => $this->missionId,
                    'video_url' => $videoUrl,
                ]);

                $socialMediaPost->updateStatus('video_uploaded', [
                    'video_url' => $videoUrl,
                    'video_uploaded_at' => now(),
                ]);

                // Clean up temporary file
                $this->cleanupTemporaryFiles($videoPath);

                // Dispatch job to send to social media
                SendToSocialMediaJob::dispatch($this->missionId);
            } else {
                throw new Exception('Failed to upload video to storage');
            }

        } catch (Exception $e) {
            Log::error('Failed to upload video to storage', [
                'mission_id' => $this->missionId,
                'error' => $e->getMessage(),
            ]);
            $socialMediaPost->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    private function uploadVideoToStorage(string $videoPath, Mission $mission): ?string
    {
        // Use Spatie Media Library to attach the video to the mission
        Log::info('Attaching video to mission using Spatie Media Library');

        try {
            $mediaItem = $mission
                ->addMedia($videoPath)
                ->withCustomProperties([
                    'type' => 'slideshow',
                    'created_for' => 'social_media',
                    'image_count' => $mission->missionPhotos()->count(),
                ])
                ->usingName('Mission Slideshow - '.$mission->school->name)
                ->usingFileName('mission_slideshow_'.$mission->id.'_'.time().'.mp4')
                ->toMediaCollection(Mission::MISSION_VIDEOS);

            $videoUrl = $mediaItem->getUrl();
            Log::info('Video attached to mission', ['url' => $videoUrl]);

            return $videoUrl;

        } catch (Exception $e) {
            Log::error('Failed to attach video to mission', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    private function cleanupTemporaryFiles(string $videoPath): void
    {
        try {
            // Get the temporary directory from the video path
            $tempDir = dirname($videoPath);

            // Check if this looks like a temporary directory
            if (str_contains($tempDir, 'mission_') && str_contains($tempDir, 'tmp')) {
                Log::info('Cleaning up temporary directory', ['path' => $tempDir]);

                // Use TemporaryDirectory to clean up safely
                $temporaryDirectory = new TemporaryDirectory($tempDir);
                $temporaryDirectory->delete();

                Log::info('Temporary files cleaned up successfully');
            }
        } catch (Exception $e) {
            Log::warning('Failed to clean up temporary files', [
                'path' => $videoPath,
                'error' => $e->getMessage(),
            ]);
            // Don't fail the job for cleanup issues
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('UploadVideoToStorageJob failed', [
            'mission_id' => $this->missionId,
            'error' => $exception->getMessage(),
        ]);

        // Mark the social media post as failed
        $socialMediaPost = MissionSocialMediaPost::where('mission_id', $this->missionId)->first();
        if ($socialMediaPost) {
            $socialMediaPost->markAsFailed($exception->getMessage());

            // Try to clean up temporary files even on failure
            if ($socialMediaPost->video_path) {
                $this->cleanupTemporaryFiles($socialMediaPost->video_path);
            }
        }
    }
}
