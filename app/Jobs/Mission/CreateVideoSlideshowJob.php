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

class CreateVideoSlideshowJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;

    public $backoff = [30, 120];

    public $timeout = 600; // 10 minutes for video processing

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
        Log::info('Creating video slideshow', ['mission_id' => $this->missionId]);

        $mission = Mission::with(['school', 'missionType'])->find($this->missionId);
        if (! $mission) {
            throw new Exception("Mission with ID {$this->missionId} not found");
        }

        $socialMediaPost = MissionSocialMediaPost::where('mission_id', $this->missionId)->first();
        if (! $socialMediaPost) {
            throw new Exception("Social media post record not found for mission {$this->missionId}");
        }

        if ($socialMediaPost->status !== 'images_processed') {
            throw new Exception("Expected status 'images_processed', but got '{$socialMediaPost->status}'");
        }

        $imageUrls = $socialMediaPost->image_urls;
        if (empty($imageUrls)) {
            $socialMediaPost->markAsFailed('No image URLs found in database');

            return;
        }

        // Update status to creating video
        $socialMediaPost->updateStatus('creating_video');

        try {
            if (count($imageUrls) === 1) {
                // Single image - dispatch directly to social media
                Log::info('Single image provided, skipping video creation', ['mission_id' => $this->missionId]);

                $socialMediaPost->updateStatus('video_created', [
                    'video_url' => $imageUrls[0],
                    'video_created_at' => now(),
                ]);

                SendToSocialMediaJob::dispatch($this->missionId);

                return;
            }

            // Multiple images - create video using FFmpeg
            $videoPath = $this->createVideoWithFFmpeg($imageUrls, $mission);

            if ($videoPath) {
                Log::info('Video created successfully', [
                    'mission_id' => $this->missionId,
                    'path' => $videoPath,
                ]);

                $socialMediaPost->updateStatus('video_created', [
                    'video_path' => $videoPath,
                    'video_created_at' => now(),
                ]);

                // Dispatch job to upload video to storage
                UploadVideoToStorageJob::dispatch($this->missionId);
            } else {
                throw new Exception('Failed to create video slideshow');
            }

        } catch (Exception $e) {
            Log::error('Failed to create video slideshow', [
                'mission_id' => $this->missionId,
                'error' => $e->getMessage(),
            ]);
            $socialMediaPost->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    private function createVideoWithFFmpeg(array $imageUrls, Mission $mission): ?string
    {
        Log::info('Setting up FFmpeg video creation', ['mission_id' => $mission->id]);

        // Create temporary directory for processing
        $temporaryDirectory = TemporaryDirectory::make()
            ->name('mission_'.$mission->id.'_'.time())
            ->create();

        $tempDir = $temporaryDirectory->path();
        Log::info('Created temporary directory', ['path' => $tempDir]);

        try {
            // Download images locally first
            $localImages = [];
            foreach ($imageUrls as $index => $imageUrl) {
                $localPath = $tempDir.'/image_'.str_pad($index + 1, 3, '0', STR_PAD_LEFT).'.jpg';

                Log::info('Downloading image', ['index' => $index + 1, 'url' => $imageUrl]);
                $imageContent = file_get_contents($imageUrl);
                if ($imageContent === false) {
                    throw new Exception('Failed to download image: '.$imageUrl);
                }

                file_put_contents($localPath, $imageContent);
                $localImages[] = $localPath;
            }

            // Create video using FFmpeg
            $outputPath = $tempDir.'/slideshow.mp4';
            $slideDuration = 4; // 4 seconds per image

            Log::info('Creating video with FFmpeg');

            // Build FFmpeg command for slideshow
            $ffmpegCommand = $this->buildFFmpegCommand($localImages, $outputPath, $slideDuration);

            // Execute FFmpeg command
            Log::info('Executing FFmpeg command', ['command' => $ffmpegCommand]);
            exec($ffmpegCommand.' 2>&1', $output, $returnCode);

            if ($returnCode !== 0) {
                Log::error('FFmpeg failed', ['output' => implode("\n", $output)]);
                throw new Exception('FFmpeg failed: '.implode("\n", $output));
            }

            if (! file_exists($outputPath)) {
                throw new Exception('Video file was not created');
            }

            Log::info('Video created successfully');

            return $outputPath;

        } catch (Exception $e) {
            // Clean up on error
            $temporaryDirectory->delete();
            throw $e;
        }
    }

    private function buildFFmpegCommand(array $imagePaths, string $outputPath, int $slideDuration): string
    {
        // Create a complex filter for slideshow that preserves full images
        $imageCount = count($imagePaths);

        if ($imageCount === 1) {
            // Single image to video - fit the entire image with letterboxing/pillarboxing
            return sprintf(
                'ffmpeg -y -loop 1 -i "%s" -t %d -vf "scale=1080:1080:force_original_aspect_ratio=decrease,pad=1080:1080:(ow-iw)/2:(oh-ih)/2:black" -c:v libx264 -pix_fmt yuv420p -r 30 "%s"',
                $imagePaths[0],
                $slideDuration,
                $outputPath
            );
        }

        // Multiple images - create slideshow with transitions
        $inputs = '';
        $filterComplex = '';
        $concat = '';

        foreach ($imagePaths as $index => $path) {
            $inputs .= sprintf('-loop 1 -t %d -i "%s" ', $slideDuration, $path);
        }

        // Build filter chain to fit full images with black padding
        for ($i = 0; $i < $imageCount; $i++) {
            $filterComplex .= sprintf('[%d:v]scale=1080:1080:force_original_aspect_ratio=decrease,pad=1080:1080:(ow-iw)/2:(oh-ih)/2:black,setsar=1,fps=30[v%d]; ', $i, $i);
        }

        // Build concatenation part
        for ($i = 0; $i < $imageCount; $i++) {
            $concat .= "[v{$i}]";
        }
        $concat .= "concat=n={$imageCount}:v=1:a=0[out]";

        $filterComplex .= $concat;

        return sprintf(
            'ffmpeg -y %s -filter_complex "%s" -map "[out]" -c:v libx264 -pix_fmt yuv420p "%s"',
            $inputs,
            $filterComplex,
            $outputPath
        );
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('CreateVideoSlideshowJob failed', [
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
