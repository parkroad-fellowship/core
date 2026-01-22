<?php

namespace App\Console\Commands\Mission;

use App\Jobs\Mission\CreateVideoSlideshowJob;
use App\Jobs\Mission\ProcessMissionImagesJob;
use App\Jobs\Mission\SendToSocialMediaJob;
use App\Jobs\Mission\UploadVideoToStorageJob;
use App\Models\MissionSocialMediaPost;
use Exception;
use Illuminate\Console\Command;

class RetrySpecificJobCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mission:retry-social-job {mission_ids : The ID(s) of the mission, comma-separated} {step : The step to retry (images|video|upload|social|all)}';

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
        $missionIdsArg = $this->argument('mission_ids');
        $step = $this->argument('step');

        // Support comma-separated mission IDs
        $missionIds = array_filter(array_map('trim', explode(',', $missionIdsArg)));
        $validSteps = ['images', 'video', 'upload', 'social', 'all'];
        if (! in_array($step, $validSteps)) {
            $this->error("Invalid step: {$step}. Valid steps are: images, video, upload, social, all");

            return 1;
        }

        $anyError = false;
        foreach ($missionIds as $missionIdRaw) {
            $missionId = (int) $missionIdRaw;
            $this->info("\n--- Mission ID: {$missionId} ---");
            $socialMediaPost = MissionSocialMediaPost::where('mission_id', $missionId)->first();

            if (! $socialMediaPost) {
                $this->error("No social media post record found for mission {$missionId}");
                $anyError = true;

                continue;
            }

            $this->info("Current status: {$socialMediaPost->status}");
            $this->info("Retrying step: {$step}");

            try {
                if ($step === 'all') {
                    // Retry all steps in order
                    $this->info('Retrying image processing...');
                    $socialMediaPost->updateStatus('pending');
                    ProcessMissionImagesJob::dispatch($missionId);

                    // Wait for images to be processed before continuing? (Not possible synchronously)
                    // So, just queue all jobs in order, user must ensure queue runs in order or rerun as needed.

                    if (empty($socialMediaPost->image_urls)) {
                        $this->warn('No image URLs found. Video step may fail if images step has not completed.');
                    }
                    $this->info('Retrying video creation...');
                    $socialMediaPost->updateStatus('images_processed');
                    CreateVideoSlideshowJob::dispatch($missionId);

                    if (! $socialMediaPost->video_path && ! $socialMediaPost->video_url) {
                        $this->warn('No video found. Upload step may fail if video step has not completed.');
                    }
                    $this->info('Retrying video upload...');
                    $socialMediaPost->updateStatus('video_created');
                    UploadVideoToStorageJob::dispatch($missionId);

                    if (! $socialMediaPost->video_url) {
                        $this->warn('No video URL found. Social step may fail if upload step has not completed.');
                    }
                    $this->info('Retrying social media posting...');
                    $socialMediaPost->updateStatus('video_uploaded');
                    SendToSocialMediaJob::dispatch($missionId);
                } else {
                    switch ($step) {
                        case 'images':
                            $this->info('Retrying image processing...');
                            $socialMediaPost->updateStatus('pending');
                            ProcessMissionImagesJob::dispatch($missionId);
                            break;

                        case 'video':
                            if (empty($socialMediaPost->image_urls)) {
                                $this->error('Cannot retry video creation: No image URLs found. Run images step first.');
                                $anyError = true;

                                continue 2;
                            }
                            $this->info('Retrying video creation...');
                            $socialMediaPost->updateStatus('images_processed');
                            CreateVideoSlideshowJob::dispatch($missionId);
                            break;

                        case 'upload':
                            if (! $socialMediaPost->video_path && ! $socialMediaPost->video_url) {
                                $this->error('Cannot retry upload: No video found. Run video step first.');
                                $anyError = true;

                                continue 2;
                            }
                            $this->info('Retrying video upload...');
                            $socialMediaPost->updateStatus('video_created');
                            UploadVideoToStorageJob::dispatch($missionId);
                            break;

                        case 'social':
                            if (! $socialMediaPost->video_url) {
                                $this->error('Cannot retry social posting: No video URL found. Run upload step first.');
                                $anyError = true;

                                continue 2;
                            }
                            $this->info('Retrying social media posting...');
                            $socialMediaPost->updateStatus('video_uploaded');
                            SendToSocialMediaJob::dispatch($missionId);
                            break;
                    }
                }

                $this->info('✅ Job has been queued for retry!');
                $this->info('💡 Monitor progress with: php artisan queue:work');

            } catch (Exception $e) {
                $this->error("Failed to retry step for mission {$missionId}: {$e->getMessage()}");
                $anyError = true;
            }
        }

        return $anyError ? 1 : 0;
    }
}
