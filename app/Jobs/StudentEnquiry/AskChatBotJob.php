<?php

namespace App\Jobs\StudentEnquiry;

use App\Enums\PRFMorphType;
use App\Models\ChatBot;
use App\Models\StudentEnquiryReply;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class AskChatBotJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 4;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $enquiryId,
        public string $content,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $previousReplies = StudentEnquiryReply::query()
            ->where([
                'student_enquiry_id' => $this->enquiryId,
            ])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $chatBot = ChatBot::query()
            ->where('name', config('prf.nlp.default_bot'))
            ->firstOrFail();

        // Build conversation history in a structured format for multi-turn dialogue
        $conversationHistory = $previousReplies
            ->map(function ($reply) use ($chatBot) {
                $role = $reply->is_from_chat_bot ? $chatBot->name : 'user';
                $content = Str::of($reply->content)->trim()->__toString();

                return [
                    'role' => $role,
                    'content' => $content,
                ];
            })
            ->reverse()
            ->values()
            ->toArray();

        // Test that the NLP is available
        if (empty(config('prf.nlp.api_key')) || empty(config('prf.nlp.base_url'))) {
            Log::warning('ChatBot API key or base URL is not configured.');

            return;
        }

        if (app()->environment('testing')) {
            Log::warning('ChatBot API is not reachable at the moment.');

            return;
        }

        $response = Http::withHeaders([
            'x-token' => config('prf.nlp.api_key'),
        ])->timeout(120)->post(config('prf.nlp.base_url').'/embedding/enquire', [
            'question' => $this->content,
            'conversation_history' => $conversationHistory,
            'stream' => false,
        ]);

        if ($response->successful()) {
            Log::info('ChatBot API response received.', [
                'response' => $response->json(),
            ]);

            $results = $response->json();

            StudentEnquiryReply::create([
                'student_enquiry_id' => $this->enquiryId,
                'content' => Str::of($results['answer'])->trim(),
                'is_from_chat_bot' => true,
                'chat_bot_payload' => $results,
                'commentorable_id' => $chatBot->id,
                'commentorable_type' => PRFMorphType::CHAT_BOT->value,
            ]);

        } elseif ($response->serverError()) {
            // Retry on 5xx errors
            Log::warning('ChatBot API returned server error, retrying...', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException('ChatBot API returned '.$response->status().'. Retrying...');
        } else {
            Log::error('ChatBot API request failed.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }
    }

    public function backoff(): array
    {
        return [10, 20, 30];
    }
}
