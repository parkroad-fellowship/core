<?php

namespace App\Jobs\Mission;

use App\Helpers\Utils;
use App\Models\Mission;
use App\Models\MissionSocialMediaPost;
use App\Services\GoogleSheetsService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

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
        if (app()->environment([
            'testing',
            'local',
            'staging',
            'development',
        ])) {
            Log::info('Skipping SendToSocialMediaJob in non-production environment');

            return;
        }
        Log::info('Sending media data to Google Sheets for Zapier processing', ['mission_id' => $this->missionId]);

        $mission = Mission::with([
            'school',
            'missionType',
            'souls',
            'souls.classGroup',
            'missionSessions',
            'missionSessions.facilitator',
            'missionSessions.speaker',
            'missionSessions.classGroup',
        ])->find($this->missionId);
        if (! $mission) {
            throw new Exception("Mission with ID {$this->missionId} not found");
        }

        $socialMediaPost = MissionSocialMediaPost::where('mission_id', $this->missionId)->first();
        if (! $socialMediaPost) {
            throw new Exception("Social media post record not found for mission {$this->missionId}");
        }

        // Can handle both video_uploaded (for multi-image videos) and video_created (for single images)
        if (! in_array($socialMediaPost->status, ['video_uploaded', 'video_created', 'completed'])) {
            throw new Exception("Expected status 'video_uploaded' or 'video_created', but got '{$socialMediaPost->status}'");
        }

        $mediaUrl = $socialMediaPost->video_url;
        if (! $mediaUrl) {
            throw new Exception('No media URL found in database');
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
        } catch (Exception $e) {
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
                throw new Exception('Media URL is not accessible: '.$response->status());
            } else {
                Log::info('Media URL is accessible');
            }
        } catch (Exception $e) {
            Log::error('Media URL validation failed', [
                'url' => $mediaUrl,
                'error' => $e->getMessage(),
            ]);
            throw new Exception('Could not validate media URL: '.$e->getMessage());
        }
    }

    private function sendToGoogleSheets(string $mediaUrl, Mission $mission): void
    {
        try {
            $baseTitle = "{$mission->school->name} - {$mission->missionType->name} Recap";
            $baseContent = '';
            $mediaUrlConverted = Utils::convertAzureURLToMediaURL($mediaUrl);

            // Generate AI-powered captions for each platform
            $generatedCaptions = $this->generatePlatformCaptions($mission);

            // Get up to 10 image URLs (images only, not videos)
            $imageUrls = [];
            if (method_exists($mission, 'missionPhotos')) {
                $photos = $mission->missionPhotos;
                foreach ($photos as $media) {
                    // Only process image files
                    if (isset($media->mime_type) && str_starts_with($media->mime_type, 'image/')) {
                        // Get the media file URL from Azure and convert
                        try {
                            $imageUrl = Utils::convertAzureURLToMediaURL(
                                $media->getTemporaryUrl(now()->addDays(3))
                            );
                            $imageUrls[] = $imageUrl;
                        } catch (Exception $e) {
                            // Skip if failed to get URL
                        }
                    }
                    if (count($imageUrls) >= 10) {
                        break;
                    }
                }
            }

            $postData = [
                'mission_id' => $mission->ulid,
                'title' => $baseTitle,
                'content' => $baseContent,
                'media_url' => $mediaUrlConverted,
                'school_name' => $mission->school->name,
                'mission_type' => $mission->missionType->name,
                'scheduled_for' => now()->addDays(3)->format('Y-m-d H:i:s'),

                // Instagram optimized content
                'instagram_caption' => $generatedCaptions['instagram']['caption'],
                'instagram_hashtags' => $generatedCaptions['instagram']['hashtags'],
                'instagram_location' => $mission->school->name,

                // Facebook optimized content
                'facebook_message' => $generatedCaptions['facebook']['message'],

                // YouTube optimized content
                'youtube_title' => $baseTitle,
                'youtube_description' => $generatedCaptions['youtube']['description'],
                'youtube_tags' => $generatedCaptions['youtube']['tags'],
                'youtube_category' => '22', // People & Blogs
                'youtube_privacy' => 'public',

                // TikTok optimized content
                'tiktok_caption' => $generatedCaptions['tiktok']['caption'],
                'tiktok_hashtags' => $generatedCaptions['tiktok']['hashtags'],
                'tiktok_privacy' => 'public',
                'tiktok_allow_comments' => 'true',
                'tiktok_allow_duet' => 'true',
                'tiktok_allow_stitch' => 'true',

                // Threads optimized content
                'threads_text' => $generatedCaptions['threads']['text'],
                'threads_reply_control' => 'everyone',

                // General settings
                'platforms' => 'instagram,facebook,youtube,tiktok,threads',
                'priority' => 'normal',
                'campaign' => 'mission-recap-'.date('Y-m'),
            ];

            // Add up to 10 image fields for Instagram
            foreach (range(1, 10) as $i) {
                $postData['image_'.$i] = $imageUrls[$i - 1] ?? null;
            }

            Log::info('Sending comprehensive post data to Google Sheets', [
                'mission_id' => $mission->id,
                'platforms' => $postData['platforms'],
                'generated_captions' => true,
                'instagram_images_count' => count($imageUrls),
            ]);

            // Use the Google Sheets service to add the row
            $googleSheetsService = app(GoogleSheetsService::class);
            $googleSheetsService->addSocialMediaPost($postData);

            Log::info('Data sent to Google Sheets successfully', [
                'mission_id' => $mission->id,
            ]);
        } catch (Exception $e) {
            Log::error('Error sending data to Google Sheets', [
                'mission_id' => $mission->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Generate platform-specific captions using AI based on mission data
     */
    private function generatePlatformCaptions(Mission $mission): array
    {
        // Prepare mission data for the prompt
        $missionData = $this->prepareMissionDataForPrompt($mission);

        $systemPrompt = $this->buildCaptionGenerationSystemPrompt();
        $userPrompt = $this->buildCaptionGenerationUserPrompt($missionData);

        Log::info('Generating platform-specific captions', [
            'mission_id' => $mission->id,
            'souls_count' => $mission->souls->count(),
            'sessions_count' => $mission->missionSessions->count(),
        ]);

        $response = $this->runPrompt($systemPrompt, $userPrompt);

        // Parse the JSON response
        try {
            // Extract JSON from markdown code blocks if present
            $jsonString = $this->extractJsonFromResponse($response);

            $captions = json_decode($jsonString, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning('Failed to parse AI response as JSON, using fallback captions', [
                    'mission_id' => $mission->id,
                    'json_error' => json_last_error_msg(),
                    'extracted_json' => $jsonString,
                    'original_response' => $response,
                ]);

                return $this->getFallbackCaptions($mission);
            }

            // Validate that all required platforms are present
            $requiredPlatforms = ['instagram', 'facebook', 'youtube', 'tiktok', 'threads'];
            $missingPlatforms = array_diff($requiredPlatforms, array_keys($captions));

            if (! empty($missingPlatforms)) {
                Log::warning('AI response missing required platforms, using fallback captions', [
                    'mission_id' => $mission->id,
                    'missing_platforms' => $missingPlatforms,
                    'received_platforms' => array_keys($captions),
                ]);

                return $this->getFallbackCaptions($mission);
            }

            Log::info('Successfully generated AI captions', [
                'mission_id' => $mission->id,
                'captions_generated' => array_keys($captions),
            ]);

            return $captions;
        } catch (Exception $e) {
            Log::error('Error parsing AI captions response', [
                'mission_id' => $mission->id,
                'error' => $e->getMessage(),
            ]);

            return $this->getFallbackCaptions($mission);
        }
    }

    /**
     * Extract JSON from AI response, handling markdown code blocks
     */
    private function extractJsonFromResponse(string $response): string
    {
        // Remove markdown code block markers if present
        $response = trim($response);

        // Handle ```json format with optional whitespace
        if (preg_match('/```json\s*(.*?)\s*```/s', $response, $matches)) {
            return trim($matches[1]);
        }

        // Handle ``` format without language specification
        if (preg_match('/```\s*(.*?)\s*```/s', $response, $matches)) {
            $content = trim($matches[1]);
            // Check if it looks like JSON
            if (str_starts_with($content, '{') && str_ends_with($content, '}')) {
                return $content;
            }
        }

        // Handle cases where JSON is wrapped in other text - find the most complete JSON object
        if (preg_match('/\{(?:[^{}]|(?:\{[^{}]*\}))*\}/s', $response, $matches)) {
            return $matches[0];
        }

        // Try to find JSON starting from first { to last }
        $firstBrace = strpos($response, '{');
        $lastBrace = strrpos($response, '}');

        if ($firstBrace !== false && $lastBrace !== false && $lastBrace > $firstBrace) {
            return substr($response, $firstBrace, $lastBrace - $firstBrace + 1);
        }

        // Return as is if no patterns match
        return $response;
    }

    /**
     * Prepare mission data for the AI prompt
     */
    private function prepareMissionDataForPrompt(Mission $mission): array
    {

        // Prepare sessions data
        $sessionsData = $mission->missionSessions->map(function ($session) {
            return [
                'class_group' => $session->classGroup?->name ?? 'General',
                'facilitator' => $session->facilitator?->full_name ?? 'Unknown',
                'speaker' => $session->speaker?->full_name ?? null,
                'notes' => $session->notes,
            ];
        });

        return [
            'school_name' => $mission->school->name,
            'mission_type' => $mission->missionType->name,
            'start_date' => $mission->start_date?->format('F j, Y'),
            'end_date' => $mission->end_date?->format('F j, Y'),
            'souls_count' => $mission->souls->count(),
            'sessions_count' => $mission->missionSessions->count(),
            'sessions' => $sessionsData->toArray(),
        ];
    }

    /**
     * Build the system prompt for caption generation
     */
    private function buildCaptionGenerationSystemPrompt(): string
    {
        return 'You are a Christian social media content creator specializing in mission outreach content. 
        Your task is to create engaging, authentic, and inspiring social media captions based on mission data from Parkroad Fellowship.
        
        IMPORTANT GUIDELINES:
        - Keep content appropriate for public sharing
        - Focus on positive impact and community transformation
        - Use inclusive, welcoming language
        - Include references to Jesus, Gospel, and Christ naturally
        - Make content engaging but authentic
        - Each platform has different audiences and tone requirements
        - Add our contact information for school bookings: 
          Phone/WhatsApp: +254 728 788 000
        
        REQUIRED HASHTAGS TO INCLUDE: #Jesus #Christ #Gospel
        
        RESPONSE FORMAT: Return ONLY a valid JSON object with this exact structure. Do NOT wrap the response in markdown code blocks or any other formatting:
        {
            "instagram": {
                "caption": "caption text here",
                "hashtags": "#Jesus #Christ #Gospel #additional #hashtags"
            },
            "facebook": {
                "message": "message text here"
            },
            "youtube": {
                "description": "description text here",
                "tags": "Jesus,Christ,Gospel,additional,tags"
            },
            "tiktok": {
                "caption": "caption text here",
                "hashtags": "#Jesus #Christ #Gospel #additional #hashtags"
            },
            "threads": {
                "text": "text here"
            }
        }';
    }

    /**
     * Build the user prompt with mission data
     */
    private function buildCaptionGenerationUserPrompt(array $missionData): string
    {
        $prompt = "Create social media captions for this mission outreach with a focus on looking back:\n\n";

        $prompt .= "MISSION DETAILS:\n";
        $prompt .= "School: {$missionData['school_name']}\n";
        $prompt .= "Mission Type: {$missionData['mission_type']}\n";

        if ($missionData['start_date']) {
            $prompt .= "Date: {$missionData['start_date']}";
            if ($missionData['end_date'] && $missionData['end_date'] !== $missionData['start_date']) {
                $prompt .= " - {$missionData['end_date']}";
            }
            $prompt .= "\n";
        }

        $prompt .= "\nSTUDENT COMMITMENTS ({$missionData['souls_count']} souls):\n";

        $prompt .= "\nSESSIONS CONDUCTED ({$missionData['sessions_count']} sessions):\n";
        if ($missionData['sessions_count'] > 0) {
            foreach ($missionData['sessions'] as $session) {
                if ($session['notes']) {
                    $prompt .= " - {$session['notes']}";
                }
                $prompt .= "\n";
            }
        } else {
            $prompt .= "- General outreach activities\n";
        }

        $prompt .= "\nCreate engaging captions that celebrate God's work, the students who made commitments, and the sessions that took place. Make it inspiring and suitable for public sharing.";

        return $prompt;
    }

    /**
     * Get fallback captions if AI generation fails
     */
    private function getFallbackCaptions(Mission $mission): array
    {
        $baseTitle = "{$mission->school->name} - {$mission->missionType->name} Recap";
        $soulsCount = $mission->souls->count();
        $sessionsCount = $mission->missionSessions->count();

        return [
            'instagram' => [
                'caption' => "🙏 Amazing time at {$mission->school->name}! ".
                    ($soulsCount > 0 ? "{$soulsCount} students made commitments to Jesus. " : '').
                    ($sessionsCount > 0 ? "Had {$sessionsCount} impactful sessions sharing the Gospel. " : '').
                    'God is moving! ✨',
                'hashtags' => '#Jesus #Christ #Gospel #missions #faith #community #outreach #school #students',
            ],
            'facebook' => [
                'message' => "🙏 {$baseTitle}\n\n".
                    "What an incredible time sharing the love of Christ at {$mission->school->name}! ".
                    ($soulsCount > 0 ? "{$soulsCount} students made commitments to follow Jesus. " : '').
                    ($sessionsCount > 0 ? "Through {$sessionsCount} sessions, we shared the Gospel and saw God work in amazing ways. " : '').
                    'Thank you to everyone who participated and supported this mission!',
            ],
            'youtube' => [
                'description' => "{$baseTitle}\n\n".
                    "Join us as we share about this incredible mission outreach at {$mission->school->name}. ".
                    ($soulsCount > 0 ? "{$soulsCount} students made commitments to Jesus, " : '').
                    ($sessionsCount > 0 ? "and through {$sessionsCount} sessions we saw God's love transform lives. " : '').
                    "This is what the Gospel in action looks like!\n\n".
                    "🙏 Thank you for supporting our missions!\n".
                    '📧 Contact us for more information about our missions.',
                'tags' => 'Jesus,Gospel,Christ,missions,faith,outreach,school,students,'.
                    strtolower($mission->school->name).','.strtolower($mission->missionType->name),
            ],
            'tiktok' => [
                'caption' => "🙏 {$mission->school->name} mission recap! ".
                    ($soulsCount > 0 ? "{$soulsCount} students said YES to Jesus! " : '').
                    'God is moving in our schools! ✨',
                'hashtags' => '#Jesus #Christ #Gospel #missions #faith #school #students #blessed #God',
            ],
            'threads' => [
                'text' => "🙏 {$baseTitle}\n\n".
                    ($soulsCount > 0 ? "{$soulsCount} students made commitments to Jesus! " : '').
                    ($sessionsCount > 0 ? "Through {$sessionsCount} sessions, we shared the Gospel and witnessed God's love in action. " : '').
                    "Grateful for every opportunity to share Christ's love with the next generation.\n\n".
                    '#Jesus #Christ #Gospel #missions #faith',
            ],
        ];
    }

    public function failed(Throwable $exception): void
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

    private function runPrompt(string $systemPrompt, string $userPrompt): string
    {
        $model = config('prf.app.gemini.model');

        $response = Http::withHeaders([
            'content-type' => 'application/json',
        ])
            ->timeout(60 * 4)
            ->withQueryParameters([
                'key' => config('prf.app.gemini.api_key'),

            ])->post(
                "https://generativelanguage.googleapis.com/v1beta/{$model}:generateContent",
                [
                    'contents' => [
                        [
                            'parts' => [
                                [
                                    'text' => $systemPrompt,
                                ],
                                [
                                    'text' => $userPrompt,
                                ],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'maxOutputTokens' => config('prf.app.gemini.max_output_tokens'),
                    ],
                ]
            );

        Log::info('Generated content', [
            'response' => $response,
        ]);

        return $response->json()['candidates'][0]['content']['parts'][0]['text'];
    }
}
