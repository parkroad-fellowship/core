<?php

namespace App\Jobs\Mission;

use App\Helpers\Utils;
use App\Models\Mission;
use App\Models\MissionSocialMediaPost;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessMissionImagesJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = [10, 30, 60];

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
        Log::info('Processing mission images', ['mission_id' => $this->missionId]);

        $mission = Mission::with(['missionPhotos', 'school', 'missionType'])->find($this->missionId);

        if (! $mission) {
            Log::error('Mission not found', ['mission_id' => $this->missionId]);
            throw new Exception("Mission with ID {$this->missionId} not found");
        }

        if ($mission->missionPhotos()->count() === 0) {
            Log::info('No photos found for mission', ['mission_id' => $this->missionId]);

            return;
        }

        // Find or create the social media post record
        $socialMediaPost = MissionSocialMediaPost::firstOrCreate(
            ['mission_id' => $this->missionId],
            ['status' => 'pending']
        );

        // Update status to processing
        $socialMediaPost->updateStatus('processing_images');

        try {
            $imageUrls = $this->getImageUrls($mission);

            if (empty($imageUrls)) {
                Log::warning('No images found for mission', ['mission_id' => $this->missionId]);
                $socialMediaPost->markAsFailed('No images found for mission');

                return;
            }

            Log::info('Found images for mission', [
                'mission_id' => $this->missionId,
                'image_count' => count($imageUrls),
            ]);

            // Save image URLs to database and update status
            $socialMediaPost->updateStatus('images_processed', [
                'image_urls' => $imageUrls,
                'images_processed_at' => now(),
            ]);

            // Dispatch the next job
            CreateVideoSlideshowJob::dispatch($this->missionId);

        } catch (Exception $e) {
            Log::error('Failed to process mission images', [
                'mission_id' => $this->missionId,
                'error' => $e->getMessage(),
            ]);
            $socialMediaPost->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    private function getImageUrls($mission): array
    {
        $imageUrls = [];

        foreach ($mission->missionPhotos as $index => $media) {
            try {
                // Skip video files - only process images
                if (in_array($media->mime_type, ['video/mp4', 'video/mpeg', 'video/quicktime', 'video/x-msvideo'])) {
                    Log::info('Skipping video file', ['file' => $media->name]);

                    continue;
                }

                // Only process image files
                if (! str_starts_with($media->mime_type, 'image/')) {
                    Log::info('Skipping non-image file', [
                        'file' => $media->name,
                        'type' => $media->mime_type,
                    ]);

                    continue;
                }

                Log::info('Processing image', ['index' => $index + 1, 'file' => $media->name]);

                // Get the media file URL from Azure
                $imageUrl = Utils::convertAzureURLToMediaURL(
                    $media->getTemporaryUrl(now()->addDays(3))
                );

                $imageUrls[] = $imageUrl;
                Log::info('Got image URL', ['url' => $imageUrl]);

            } catch (Exception $e) {
                Log::error('Failed to get image URL', [
                    'index' => $index + 1,
                    'error' => $e->getMessage(),
                ]);
                // Continue processing other images even if one fails
            }
        }

        return $imageUrls;
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('ProcessMissionImagesJob failed', [
            'mission_id' => $this->missionId,
            'error' => $exception->getMessage(),
        ]);

        // Mark the social media post as failed
        $socialMediaPost = MissionSocialMediaPost::where('mission_id', $this->missionId)->first();
        if ($socialMediaPost) {
            $socialMediaPost->markAsFailed($exception->getMessage());
        }
    }
}
