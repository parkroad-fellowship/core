<?php

namespace App\Console\Commands\Mission;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Spatie\TemporaryDirectory\TemporaryDirectory;

class TestSocialUploadCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-social-upload';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create video slideshow from mission images for social media posting';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $mission = \App\Models\Mission::with('media')->where('id', 47)->first();

        if ($mission && $mission->media->count() > 0) {
            $this->info('Found mission with '.$mission->media->count().' media files.');

            try {
                // Get image URLs directly from Azure
                $imageUrls = $this->getImageUrls($mission);

                if (empty($imageUrls)) {
                    $this->error('No images were found for the mission.');
                    return;
                }

                // Create video content from images
                $videoUrl = $this->createVideoFromImages($imageUrls, $mission);

                if ($videoUrl) {
                    $this->info('Video created successfully: '.$videoUrl);

                    // Send video to Buffer/social media
                    $this->sendVideoToBuffer($videoUrl, $mission);
                } else {
                    $this->error('Failed to create video content.');
                }
            } catch (\Exception $e) {
                $this->error('Error processing mission media: '.$e->getMessage());
            }
        } else {
            $this->info('No mission found with media.');
        }
    }

    private function getImageUrls($mission)
    {
        $imageUrls = [];

        foreach ($mission->media as $index => $media) {
            try {
                $this->info('Getting image URL '.($index + 1).'...');

                // Get the media file URL from Azure
                $imageUrl = Str::of($media->getTemporaryUrl(now()->addDays(3)))
                    ->replace('prfcorestorage.blob.core.windows.net', 'media.parkroadfellowship.org')
                    ->__toString();

                $imageUrls[] = $imageUrl;
                $this->info('Got image URL: '.$imageUrl);
            } catch (\Exception $e) {
                $this->error('Failed to get image URL '.($index + 1).': '.$e->getMessage());
            }
        }

        return $imageUrls;
    }

    private function createVideoFromImages($imageUrls, $mission)
    {
        $this->info('Creating video from ' . count($imageUrls) . ' images...');
        
        if (empty($imageUrls)) {
            $this->error('No images provided for video creation');
            return null;
        }

        if (count($imageUrls) === 1) {
            // Single image - return the image URL (Buffer can handle images)
            $this->info('Single image provided, returning image URL');
            return $imageUrls[0];
        }

        // Multiple images - create video using FFmpeg
        return $this->createVideoWithFFmpeg($imageUrls, $mission);
    }
    
    private function createVideoWithFFmpeg($imageUrls, $mission)
    {
        $this->info('Setting up FFmpeg video creation...');
        
        // Create temporary directory for processing
        $temporaryDirectory = TemporaryDirectory::make()
            ->name('mission_' . $mission->id . '_' . time())
            ->create();
        
        $tempDir = $temporaryDirectory->path();
        $this->info('Created temporary directory: ' . $tempDir);
        
        try {
            // Download images locally first
            $localImages = [];
            foreach ($imageUrls as $index => $imageUrl) {
                $localPath = $tempDir . '/image_' . str_pad($index + 1, 3, '0', STR_PAD_LEFT) . '.jpg';
                
                $this->info('Downloading image ' . ($index + 1) . '...');
                $imageContent = file_get_contents($imageUrl);
                if ($imageContent === false) {
                    throw new \Exception('Failed to download image: ' . $imageUrl);
                }
                
                file_put_contents($localPath, $imageContent);
                $localImages[] = $localPath;
            }
            
            // Create video using FFmpeg
            $outputPath = $tempDir . '/slideshow.mp4';
            $slideDuration = 3; // 3 seconds per image
            
            $this->info('Creating video with FFmpeg...');
            
            // Build FFmpeg command for slideshow
            $ffmpegCommand = $this->buildFFmpegCommand($localImages, $outputPath, $slideDuration);
            
            // Execute FFmpeg command
            $this->info('Executing: ' . $ffmpegCommand);
            exec($ffmpegCommand . ' 2>&1', $output, $returnCode);
            
            if ($returnCode !== 0) {
                throw new \Exception('FFmpeg failed: ' . implode("\n", $output));
            }
            
            if (!file_exists($outputPath)) {
                throw new \Exception('Video file was not created');
            }
            
            $this->info('Video created successfully!');
            
            // Upload the video to a storage location and return URL
            $videoUrl = $this->uploadVideoToStorage($outputPath, $mission);
            
            // Clean up temporary files automatically
            $temporaryDirectory->delete();
            
            return $videoUrl;
            
        } catch (\Exception $e) {
            // Clean up on error
            $temporaryDirectory->delete();
            throw $e;
        }
    }
    
    private function buildFFmpegCommand($imagePaths, $outputPath, $slideDuration)
    {
        // Create a complex filter for slideshow with crossfade transitions
        $imageCount = count($imagePaths);
        
        if ($imageCount === 1) {
            // Single image to video
            return sprintf(
                'ffmpeg -y -loop 1 -i "%s" -t %d -vf "scale=1080:1080:force_original_aspect_ratio=increase,crop=1080:1080" -c:v libx264 -pix_fmt yuv420p -r 30 "%s"',
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
        
        // Build filter chain for crossfade transitions
        for ($i = 0; $i < $imageCount; $i++) {
            $filterComplex .= sprintf('[%d:v]scale=1080:1080:force_original_aspect_ratio=increase,crop=1080:1080,setsar=1,fps=30[v%d]; ', $i, $i);
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
    
    private function uploadVideoToStorage($videoPath, $mission)
    {
        // Use Spatie Media Library to attach the video to the mission
        $this->info('Attaching video to mission using Spatie Media Library...');
        
        try {
            $mediaItem = $mission
                ->addMedia($videoPath)
                ->withCustomProperties([
                    'type' => 'slideshow',
                    'created_for' => 'social_media',
                    'image_count' => $mission->media()->count(),
                ])
                ->usingName('Mission Slideshow - ' . $mission->school->name)
                ->usingFileName('mission_slideshow_' . $mission->id . '_' . time() . '.mp4')
                ->toMediaCollection(\App\Models\Mission::MISSION_VIDEOS);
            
            $videoUrl = $mediaItem->getUrl();
            $this->info('Video attached to mission: ' . $videoUrl);
            
            return $videoUrl;
            
        } catch (\Exception $e) {
            $this->error('Failed to attach video to mission: ' . $e->getMessage());
            throw $e;
        }
    }
    
    private function sendVideoToBuffer($mediaUrl, $mission)
    {
        // Quick validation that the media URL is accessible
        $this->info('Validating media URL...');
        
        try {
            $response = Http::timeout(10)->head($mediaUrl);
            if (!$response->successful()) {
                $this->warn('Media URL returned status: ' . $response->status());
            } else {
                $this->info('Media URL is accessible!');
            }
        } catch (\Exception $e) {
            $this->warn('Could not validate media URL: ' . $e->getMessage());
        }
        
        try {
            $postData = [
                'title' => $mission->school->name.' - Mission Showcase',
                'content' => $mission->executive_summary,
                'media_url' => $mediaUrl, // Changed from video_url to media_url
                'scheduled_for' => now()->addDays(3),
                'type' => 'media_post', // Changed from video_reel
            ];

            // Send to Buffer/social media API
            $response = Http::withHeaders([
                'x-make-apikey' => config('prf.hooks.make.social_engine.api_key'),
            ])->post(config('prf.hooks.make.social_engine.webhook_url'), $postData);

            if ($response->successful()) {
                $this->info('Media sent to Buffer successfully!');
            } else {
                $this->error('Failed to send media to Buffer: '.$response->body());
            }
        } catch (\Exception $e) {
            $this->error('Error sending media to Buffer: '.$e->getMessage());
        }
    }
}
