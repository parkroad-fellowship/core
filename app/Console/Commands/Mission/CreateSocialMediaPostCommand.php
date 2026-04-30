<?php

namespace App\Console\Commands\Mission;

use App\Enums\PRFMissionStatus;
use App\Jobs\Mission\ProcessMissionImagesJob;
use App\Models\Mission;
use Exception;
use Illuminate\Console\Command;

class CreateSocialMediaPostCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mission:create-social-post 
                          {mission_id? : The ID of the mission to create social media post for}
                          {--all : Process all missions that have photos but no social media posts}
                          {--limit=10 : Maximum number of missions to process when using --all flag}';

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
        if ($this->option('all')) {
            return $this->handleAllMissions();
        }

        $missionId = $this->argument('mission_id');

        if (! $missionId) {
            $this->error('Please provide a mission_id or use the --all flag');

            return 1;
        }

        return $this->handleSingleMission($missionId);
    }

    /**
     * Process all eligible missions
     */
    private function handleAllMissions(): int
    {
        $limit = (int) $this->option('limit');

        $this->info('🔍 Looking for missions with photos...');

        // Get missions that have photos
        $missions = Mission::query()
            ->with(['school', 'missionType'])
            ->where('status', PRFMissionStatus::SERVICED)
            ->whereHas('missionPhotos')
            ->orderBy('start_date', 'asc')
            ->limit($limit)
            ->get();

        if ($missions->isEmpty()) {
            $this->info('✅ No missions found that need social media posts created.');

            return 0;
        }

        $this->info("📋 Found {$missions->count()} missions to process:");

        foreach ($missions as $mission) {
            $photoCount = $mission->missionPhotos()->count();
            $this->line("  • Mission #{$mission->id}: {$mission->school->name} - {$mission->missionType->name} ({$photoCount} photos)");
        }

        if (! $this->confirm("\n🚀 Do you want to queue social media post creation for these {$missions->count()} missions?")) {
            $this->info('Operation cancelled.');

            return 0;
        }

        $this->info("\n⏳ Queuing jobs for all missions...");
        $processed = 0;

        foreach ($missions as $mission) {
            try {
                ProcessMissionImagesJob::dispatch($mission->id);
                $this->info("  ✅ Queued: {$mission->school->name} - {$mission->missionType->name}");
                $processed++;
            } catch (Exception $e) {
                $this->error("  ❌ Failed: {$mission->school->name} - {$e->getMessage()}");
            }
        }

        $this->info("\n🎉 Successfully queued {$processed} missions for social media post creation!");
        $this->info('💡 Monitor progress with: php artisan queue:work');
        $this->info('📊 Check job status in logs or mission_social_media_posts table');

        return 0;
    }

    /**
     * Process a single mission
     */
    private function handleSingleMission(int $missionId): int
    {
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
