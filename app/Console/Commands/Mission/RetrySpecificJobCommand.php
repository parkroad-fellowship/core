<?php

namespace App\Console\Commands\Mission;

use App\Jobs\Mission\CreateVideoSlideshowJob;
use App\Jobs\Mission\ProcessMissionImagesJob;
use App\Jobs\Mission\SendToSocialMediaJob;
use App\Jobs\Mission\UploadVideoToStorageJob;
use App\Models\MissionSocialMediaPost;
use Illuminate\Console\Command;

class RetrySpecificJobCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mission:retry-social-job {mission_id : The ID of the mission} {step : The step to retry (images|video|upload|social)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retry a specific step in the social media post creation workflow';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $missionId = (int) $this->argument('mission_id');
        $step = $this->argument('step');

        $socialMediaPost = MissionSocialMediaPost::where('mission_id', $missionId)->first();

        if (! $socialMediaPost) {
            $this->error("No social media post record found for mission {$missionId}");

            return 1;
        }

        if ($socialMediaPost->isCompleted()) {
            $this->warn("Social media post for mission {$missionId} is already completed.");
            $this->info("Current status: {$socialMediaPost->status}");

            return 0;
        }

        $this->info("Current status: {$socialMediaPost->status}");
        $this->info("Retrying step: {$step}");

        try {
            switch ($step) {
                case 'images':
                    $this->info('Retrying image processing...');
                    $socialMediaPost->updateStatus('pending');
                    ProcessMissionImagesJob::dispatch($missionId);
                    break;

                case 'video':
                    if (empty($socialMediaPost->image_urls)) {
                        $this->error('Cannot retry video creation: No image URLs found. Run images step first.');

                        return 1;
                    }
                    $this->info('Retrying video creation...');
                    $socialMediaPost->updateStatus('images_processed');
                    CreateVideoSlideshowJob::dispatch($missionId);
                    break;

                case 'upload':
                    if (! $socialMediaPost->video_path && ! $socialMediaPost->video_url) {
                        $this->error('Cannot retry upload: No video found. Run video step first.');

                        return 1;
                    }
                    $this->info('Retrying video upload...');
                    $socialMediaPost->updateStatus('video_created');
                    UploadVideoToStorageJob::dispatch($missionId);
                    break;

                case 'social':
                    if (! $socialMediaPost->video_url) {
                        $this->error('Cannot retry social posting: No video URL found. Run upload step first.');

                        return 1;
                    }
                    $this->info('Retrying social media posting...');
                    $socialMediaPost->updateStatus('video_uploaded');
                    SendToSocialMediaJob::dispatch($missionId);
                    break;

                default:
                    $this->error("Invalid step: {$step}. Valid steps are: images, video, upload, social");

                    return 1;
            }

            $this->info('✅ Job has been queued for retry!');
            $this->info('💡 Monitor progress with: php artisan queue:work');

            return 0;

        } catch (\Exception $e) {
            $this->error("Failed to retry step: {$e->getMessage()}");

            return 1;
        }
    }
}
