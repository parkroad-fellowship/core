<?php

namespace App\Console\Commands\Mission;

use App\Jobs\Mission\ProcessMissionImagesJob;
use App\Models\Mission;
use Illuminate\Console\Command;

class CreateSocialMediaPostCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mission:create-social-post {mission_id : The ID of the mission to create social media post for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create and schedule social media post from mission images using job queue';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $missionId = $this->argument('mission_id');

        $mission = Mission::with(['media', 'school', 'missionType'])
            ->where('id', $missionId)
            ->first();

        if (! $mission) {
            $this->error("Mission with ID {$missionId} not found.");

            return 1;
        }

        if ($mission->missionPhotos()->count() === 0) {
            $this->error('Mission has no photos to process.');

            return 1;
        }

        $this->info("Starting social media post creation for mission: {$mission->school->name}");
        $this->info("Found {$mission->missionPhotos()->count()} photos to process.");

        // Dispatch the first job in the chain with mission ID only
        ProcessMissionImagesJob::dispatch($mission->id);

        $this->info('✅ Social media post creation jobs have been queued!');
        $this->info('You can monitor job progress in the mission_social_media_posts table or logs.');

        return 0;
    }
}
