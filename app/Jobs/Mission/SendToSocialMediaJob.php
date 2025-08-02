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
        Log::info('Sending media to social platforms', ['mission_id' => $this->missionId]);

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

            // Send to Buffer/social media
            $postId = $this->sendToBuffer($mediaUrl, $mission);

            // Mark as completed
            $socialMediaPost->updateStatus('completed', [
                'social_media_post_id' => $postId,
                'sent_to_social_at' => now(),
            ]);

            Log::info('Media sent to social platforms successfully', [
                'mission_id' => $this->missionId,
                'post_id' => $postId,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send media to social platforms', [
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

    private function sendToBuffer(string $mediaUrl, Mission $mission): ?string
    {
        try {
            $postData = [
                'title' => "{$mission->school->name} - {$mission->missionType->name} Recap",
                'content' => $mission->executive_summary,
                'media_url' => Utils::convertAzureURLToMediaURL($mediaUrl),
                'scheduled_for' => now()->addDays(3),
                'type' => 'media_post',
            ];

            Log::info('Sending post data to Buffer', [
                'mission_id' => $mission->id,
                'post_data' => $postData,
            ]);

            // Send to Buffer/social media API
            $response = Http::withHeaders([
                'x-make-apikey' => config('prf.hooks.make.social_engine.api_key'),
            ])->post(config('prf.hooks.make.social_engine.webhook_url'), $postData);

            if ($response->successful()) {
                $responseData = $response->json();
                Log::info('Media sent to Buffer successfully', [
                    'mission_id' => $mission->id,
                    'response' => $responseData,
                ]);

                // Return post ID if available
                return $responseData['post_id'] ?? $responseData['id'] ?? 'success';
            } else {
                Log::error('Failed to send media to Buffer', [
                    'mission_id' => $mission->id,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
                throw new \Exception('Failed to send media to Buffer: '.$response->body());
            }
        } catch (\Exception $e) {
            Log::error('Error sending media to Buffer', [
                'mission_id' => $mission->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
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
