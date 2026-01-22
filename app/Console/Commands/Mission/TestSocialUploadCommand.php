<?php

namespace App\Console\Commands\Mission;

use App\Jobs\Mission\ProcessMissionImagesJob;
use App\Models\Mission;
use Exception;
use Illuminate\Console\Command;

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
        $mission = Mission::with(['media', 'school', 'missionType'])->where('id', 47)->first();

        if ($mission && $mission->missionPhotos()->count() > 0) {
            $this->info('Found mission with '.$mission->missionPhotos()->count().' media files.');

            try {
                $this->info('🚀 Starting social media post creation using job queue...');

                // Dispatch the first job in the chain with mission ID only
                ProcessMissionImagesJob::dispatch($mission->id);

                $this->info('✅ Social media post creation jobs have been queued!');
                $this->info('📋 Jobs will process in this order:');
                $this->info('   1. ProcessMissionImagesJob - Extract and validate image URLs');
                $this->info('   2. CreateVideoSlideshowJob - Create video from images using FFmpeg');
                $this->info('   3. UploadVideoToStorageJob - Upload video to Azure storage');
                $this->info('   4. SendToSocialMediaJob - Send to Google Sheets for Zapier processing');
                $this->info('');
                $this->info('💡 Monitor progress with: php artisan queue:work');
                $this->info('📊 Check job status in logs or mission_social_media_posts table');

            } catch (Exception $e) {
                $this->error('Error queuing social media jobs: '.$e->getMessage());
            }
        } else {
            $this->info('No mission found with media.');
        }
    }
}
