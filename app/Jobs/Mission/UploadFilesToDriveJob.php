<?php

namespace App\Jobs\Mission;

use App\Helpers\Utils;
use App\Models\Mission;
use App\Models\MissionSocialMediaPost;
use App\Services\GoogleDriveService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class UploadFilesToDriveJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = [30, 90, 180];

    public $timeout = 600; // 10 minutes for file uploads

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $missionId,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Uploading mission files to Google Drive', ['mission_id' => $this->missionId]);

        $mission = Mission::with(['missionPhotos', 'school', 'missionType'])->find($this->missionId);

        if (! $mission) {
            Log::error('Mission not found', ['mission_id' => $this->missionId]);
            throw new Exception("Mission with ID {$this->missionId} not found");
        }

        $allMedia = collect([...$mission->missionPhotos, ...$mission->missionVideos]);

        if ($allMedia->count() === 0) {
            Log::info('No media files found for mission', ['mission_id' => $this->missionId]);

            return;
        }

        try {
            $mediaFiles = $this->prepareMediaFiles($allMedia);

            if (empty($mediaFiles)) {
                Log::warning('No valid media files found for mission', ['mission_id' => $this->missionId]);

                return;
            }

            Log::info('Prepared media files for upload', [
                'mission_id' => $this->missionId,
                'file_count' => count($mediaFiles),
            ]);

            // Upload files to Google Drive
            $uploadResult = (new GoogleDriveService)->uploadMissionFiles($mission, $mediaFiles);

            Log::info('Successfully uploaded mission files to Google Drive', [
                'mission_id' => $this->missionId,
                'uploaded_count' => count($uploadResult['uploaded_files']),
                'error_count' => count($uploadResult['errors']),
                'mission_folder_id' => $uploadResult['mission_folder_id'],
            ]);
        } catch (Exception $e) {
            Log::error('Failed to upload mission files to Google Drive', [
                'mission_id' => $this->missionId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Prepare media files for upload
     */
    private function prepareMediaFiles($mediaCollection): array
    {
        $mediaFiles = [];

        foreach ($mediaCollection as $media) {
            try {
                // Get the media file URL with extended expiry
                $mediaUrl = Utils::convertAzureURLToMediaURL(
                    $media->getTemporaryUrl(now()->addDays(1))
                );

                // Determine file type
                $isImage = str_starts_with($media->mime_type, 'image/');
                $isVideo = str_starts_with($media->mime_type, 'video/');

                if (! $isImage && ! $isVideo) {
                    Log::info('Skipping non-media file', [
                        'file' => $media->name,
                        'type' => $media->mime_type,
                    ]);

                    continue;
                }

                // Generate a clean filename
                $extension = pathinfo($media->name, PATHINFO_EXTENSION);
                $cleanName = $this->generateCleanFileName($media, $isImage, $extension);

                $mediaFiles[] = [
                    'name' => $cleanName,
                    'url' => $mediaUrl,
                    'mime_type' => $media->mime_type,
                    'size' => $media->size,
                    'type' => $isImage ? 'image' : 'video',
                    'original_name' => $media->name,
                    'collection' => $media->collection_name,
                ];

                Log::info('Prepared media file for upload', [
                    'original_name' => $media->name,
                    'clean_name' => $cleanName,
                    'type' => $isImage ? 'image' : 'video',
                    'url' => $mediaUrl,
                ]);
            } catch (Exception $e) {
                Log::error('Failed to prepare media file', [
                    'file' => $media->name,
                    'error' => $e->getMessage(),
                ]);
                // Continue with other files
            }
        }

        return $mediaFiles;
    }

    /**
     * Generate a clean filename for the media file
     */
    private function generateCleanFileName($media, bool $isImage, string $extension): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $type = $isImage ? 'photo' : 'video';
        $collection = str_replace('-', '_', $media->collection_name);

        return "mission_{$this->missionId}_{$collection}_{$type}_{$timestamp}.{$extension}";
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('UploadFilesToDriveJob failed', [
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
