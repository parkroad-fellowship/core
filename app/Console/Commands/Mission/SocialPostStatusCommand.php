<?php

namespace App\Console\Commands\Mission;

use App\Models\MissionSocialMediaPost;
use Illuminate\Console\Command;

class SocialPostStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mission:social-status {mission_id? : The ID of a specific mission to check}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check the status of social media post creation for missions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $missionId = $this->argument('mission_id');

        if ($missionId) {
            $this->showSpecificMissionStatus((int) $missionId);
        } else {
            $this->showAllMissionStatuses();
        }

        return 0;
    }

    private function showSpecificMissionStatus(int $missionId): void
    {
        $socialMediaPost = MissionSocialMediaPost::with('mission.school')
            ->where('mission_id', $missionId)
            ->first();

        if (! $socialMediaPost) {
            $this->error("No social media post record found for mission {$missionId}");

            return;
        }

        $this->info("📊 Social Media Post Status for Mission {$missionId}");
        $this->info("School: {$socialMediaPost->mission->school->name}");
        $this->line('');

        $this->displayMissionDetails($socialMediaPost);
    }

    private function showAllMissionStatuses(): void
    {
        $socialMediaPosts = MissionSocialMediaPost::with('mission.school')
            ->orderBy('start_date', 'asc')
            ->limit(20)
            ->get();

        if ($socialMediaPosts->isEmpty()) {
            $this->info('No social media posts found.');

            return;
        }

        $this->info('📊 Recent Social Media Post Statuses (Last 20)');
        $this->line('');

        $headers = ['Mission ID', 'School', 'Status', 'Created', 'Last Updated'];
        $rows = [];

        foreach ($socialMediaPosts as $post) {
            $rows[] = [
                $post->mission_id,
                $post->mission->school->name ?? 'N/A',
                $this->formatStatus($post->status),
                $post->created_at->format('M j, Y H:i'),
                $post->updated_at->format('M j, Y H:i'),
            ];
        }

        $this->table($headers, $rows);

        $this->line('');
        $this->info('💡 Use: mission:social-status {mission_id} for detailed status');
    }

    private function displayMissionDetails(MissionSocialMediaPost $post): void
    {
        $this->info("Status: {$this->formatStatus($post->status)}");

        if ($post->error_message) {
            $this->error("Error: {$post->error_message}");
        }

        $this->line('');
        $this->info('📋 Step Progress:');

        // Images
        if ($post->images_processed_at) {
            $this->info("✅ Images processed at: {$post->images_processed_at->format('M j, Y H:i:s')}");
            $imageCount = $post->image_urls ? count($post->image_urls) : 0;
            $this->line("   📸 Found {$imageCount} images");
        } else {
            $this->line('⏳ Images: Not processed');
        }

        // Video
        if ($post->video_created_at) {
            $this->info("✅ Video created at: {$post->video_created_at->format('M j, Y H:i:s')}");
            if ($post->video_path) {
                $this->line("   📹 Video path: {$post->video_path}");
            }
        } else {
            $this->line('⏳ Video: Not created');
        }

        // Upload
        if ($post->video_uploaded_at) {
            $this->info("✅ Video uploaded at: {$post->video_uploaded_at->format('M j, Y H:i:s')}");
            if ($post->video_url) {
                $this->line("   🔗 Video URL: {$post->video_url}");
            }
        } else {
            $this->line('⏳ Upload: Not completed');
        }

        // Social
        if ($post->sent_to_social_at) {
            $this->info("✅ Sent to social at: {$post->sent_to_social_at->format('M j, Y H:i:s')}");
            if ($post->social_media_post_id) {
                $this->line("   🆔 Social post ID: {$post->social_media_post_id}");
            }
        } else {
            $this->line('⏳ Social: Not sent');
        }

        $this->line('');
        $this->info("📅 Created: {$post->created_at->format('M j, Y H:i:s')}");
        $this->info("🔄 Updated: {$post->updated_at->format('M j, Y H:i:s')}");

        if ($post->isFailed()) {
            $this->line('');
            $this->info('💡 To retry, use: mission:retry-social-job '.$post->mission_id.' {step}');
            $this->line('   Available steps: images, video, upload, social');
        }
    }

    private function formatStatus(string $status): string
    {
        return match ($status) {
            'pending' => '🔄 Pending',
            'processing_images' => '📸 Processing Images',
            'images_processed' => '✅ Images Processed',
            'creating_video' => '🎬 Creating Video',
            'video_created' => '✅ Video Created',
            'uploading_video' => '📤 Uploading Video',
            'video_uploaded' => '✅ Video Uploaded',
            'sending_to_social' => '📱 Sending to Social',
            'completed' => '🎉 Completed',
            'failed' => '❌ Failed',
            default => "❓ {$status}",
        };
    }
}
