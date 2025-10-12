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

class AskChatBotJob implements ShouldQueue
{
    use Queueable;

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
        $response = Http::withHeaders([
            'x-token' => config('prf.nlp.api_key'),
        ])->post(config('prf.nlp.base_url').'/embedding/enquire', [
            'question' => $this->content,
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
                'commentorable_id' => ChatBot::query()
                    ->where('name', config('prf.nlp.default_bot'))
                    ->firstOrFail()
                    ->id,
                'commentorable_type' => PRFMorphType::CHAT_BOT->value,
            ]);

        } else {
            Log::error('ChatBot API request failed.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }
    }
}
