<?php

namespace App\Jobs\Mission;

use App\Helpers\Utils;
use App\Models\Mission;
use App\Models\MissionSocialMediaPost;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendToSocialMediaJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = [60, 180, 300];

    public $timeout = 120; // 2 minutes for API calls

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
        Log::info('Sending media data to Google Sheets for Zapier processing', ['mission_id' => $this->missionId]);

        $mission = Mission::with(['school', 'missionType'])->find($this->missionId);
        if (! $mission) {
            throw new \Exception("Mission with ID {$this->missionId} not found");
        }

        $socialMediaPost = MissionSocialMediaPost::where('mission_id', $this->missionId)->first();
        if (! $socialMediaPost) {
            throw new \Exception("Social media post record not found for mission {$this->missionId}");
        }

        // Can handle both video_uploaded (for multi-image videos) and video_created (for single images)
        if (! in_array($socialMediaPost->status, ['video_uploaded', 'video_created', 'completed'])) {
            throw new \Exception("Expected status 'video_uploaded' or 'video_created', but got '{$socialMediaPost->status}'");
        }

        $mediaUrl = $socialMediaPost->video_url;
        if (! $mediaUrl) {
            throw new \Exception('No media URL found in database');
        }

        // Update status to sending
        $socialMediaPost->updateStatus('sending_to_social');

        try {
            // Validate media URL is accessible
            $this->validateMediaUrl($mediaUrl);

            // Send to Google Sheets to trigger Zapier workflow
            $this->sendToGoogleSheets($mediaUrl, $mission);

            // Mark as completed
            $socialMediaPost->updateStatus('completed', [
                'sent_to_social_at' => now(),
            ]);

            Log::info('Media data sent to Google Sheets successfully', [
                'mission_id' => $this->missionId,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send media data to Google Sheets', [
                'mission_id' => $this->missionId,
                'error' => $e->getMessage(),
            ]);
            $socialMediaPost->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    private function validateMediaUrl(string $mediaUrl): void
    {
        Log::info('Validating media URL accessibility');

        try {
            $response = Http::timeout(10)->head($mediaUrl);
            if (! $response->successful()) {
                Log::warning('Media URL returned unsuccessful status', [
                    'status' => $response->status(),
                    'url' => $mediaUrl,
                ]);
                throw new \Exception('Media URL is not accessible: '.$response->status());
            } else {
                Log::info('Media URL is accessible');
            }
        } catch (\Exception $e) {
            Log::error('Media URL validation failed', [
                'url' => $mediaUrl,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception('Could not validate media URL: '.$e->getMessage());
        }
    }

    private function sendToGoogleSheets(string $mediaUrl, Mission $mission): void
    {
        try {
            $baseTitle = "{$mission->school->name} - {$mission->missionType->name} Recap";
            $baseContent = $mission->executive_summary;
            $mediaUrlConverted = Utils::convertAzureURLToMediaURL($mediaUrl);

            $postData = [
                'mission_id' => $mission->ulid,
                'title' => $baseTitle,
                'content' => $baseContent,
                'media_url' => $mediaUrlConverted,
                'school_name' => $mission->school->name,
                'mission_type' => $mission->missionType->name,
                'scheduled_for' => now()->addDays(3)->format('Y-m-d H:i:s'),

                // Instagram optimized content
                'instagram_caption' => $this->createInstagramCaption($mission),
                'instagram_hashtags' => $this->createInstagramHashtags($mission),
                'instagram_location' => $mission->school->name,

                // Facebook optimized content
                'facebook_message' => $this->createFacebookMessage($mission),

                // YouTube optimized content
                'youtube_title' => $baseTitle,
                'youtube_description' => $this->createYouTubeDescription($mission),
                'youtube_tags' => $this->createYouTubeTags($mission),
                'youtube_category' => '22', // People & Blogs
                'youtube_privacy' => 'public',

                // TikTok optimized content
                'tiktok_caption' => $this->createTikTokCaption($mission),
                'tiktok_hashtags' => $this->createTikTokHashtags($mission),
                'tiktok_privacy' => 'public',
                'tiktok_allow_comments' => 'true',
                'tiktok_allow_duet' => 'true',
                'tiktok_allow_stitch' => 'true',

                // Threads optimized content
                'threads_text' => $this->createThreadsText($mission),
                'threads_reply_control' => 'everyone',

                // General settings
                'platforms' => 'instagram,facebook,youtube,tiktok,threads',
                'priority' => 'normal',
                'campaign' => 'mission-recap-'.date('Y-m'),
            ];

            Log::info('Sending comprehensive post data to Google Sheets', [
                'mission_id' => $mission->id,
                'platforms' => $postData['platforms'],
            ]);

            // Use the Google Sheets service to add the row
            $googleSheetsService = app(\App\Services\GoogleSheetsService::class);
            $googleSheetsService->addSocialMediaPost($postData);

            Log::info('Data sent to Google Sheets successfully', [
                'mission_id' => $mission->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Error sending data to Google Sheets', [
                'mission_id' => $mission->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function createInstagramCaption(Mission $mission): string
    {
        return "🙏 {$mission->school->name} - {$mission->missionType->name} Recap\n\n".
               substr($mission->executive_summary, 0, 200).
               (strlen($mission->executive_summary) > 200 ? '...' : '').
               "\n\n#missions #faith #community #outreach";
    }

    private function createInstagramHashtags(Mission $mission): string
    {
        $baseHashtags = ['#missions', '#faith', '#community', '#outreach', '#church'];
        $schoolHashtag = '#'.str_replace([' ', '-', '.'], '', strtolower($mission->school->name));
        $missionHashtag = '#'.str_replace([' ', '-', '.'], '', strtolower($mission->missionType->name));

        return implode(' ', array_merge($baseHashtags, [$schoolHashtag, $missionHashtag]));
    }

    private function createFacebookMessage(Mission $mission): string
    {
        return "🙏 {$mission->school->name} - {$mission->missionType->name} Recap\n\n".
               $mission->executive_summary."\n\n".
               'Thank you to everyone who participated and supported this mission!';
    }

    private function createYouTubeDescription(Mission $mission): string
    {
        return "{$mission->school->name} - {$mission->missionType->name} Recap\n\n".
               $mission->executive_summary."\n\n".
               "🙏 Thank you for watching and supporting our missions!\n".
               "📧 Contact us for more information about our outreach programs.\n\n".
               'Tags: missions, faith, community, outreach, church, '.
               strtolower($mission->school->name).', '.
               strtolower($mission->missionType->name);
    }

    private function createYouTubeTags(Mission $mission): string
    {
        $tags = [
            'missions',
            'faith',
            'community',
            'outreach',
            'church',
            strtolower($mission->school->name),
            strtolower($mission->missionType->name),
            'charity',
            'nonprofit',
            'volunteer',
        ];

        return implode(',', $tags);
    }

    private function createTikTokCaption(Mission $mission): string
    {
        return "🙏 {$mission->school->name} making a difference! ".
               substr($mission->executive_summary, 0, 100).
               (strlen($mission->executive_summary) > 100 ? '...' : '');
    }

    private function createTikTokHashtags(Mission $mission): string
    {
        return '#missions #faith #community #outreach #church #volunteer #charity #nonprofit #makingadifference #blessed';
    }

    private function createThreadsText(Mission $mission): string
    {
        return "🙏 {$mission->school->name} - {$mission->missionType->name} Recap\n\n".
               substr($mission->executive_summary, 0, 400).
               (strlen($mission->executive_summary) > 400 ? '...' : '').
               "\n\n#missions #faith #community";
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendToSocialMediaJob failed', [
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
