<?php

namespace App\Jobs\StudentEnquiry;

use App\Contracts\Services\NLPServiceInterface;
use App\Enums\PRFMorphType;
use App\Models\ChatBot;
use App\Models\StudentEnquiryReply;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;

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
    public function handle(NLPServiceInterface $nlp): void
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

        $result = $nlp->enquire($this->content, $conversationHistory);

        if (empty($result['answer'])) {
            return;
        }

        StudentEnquiryReply::create([
            'student_enquiry_id' => $this->enquiryId,
            'content' => Str::of($result['answer'])->trim(),
            'is_from_chat_bot' => true,
            'chat_bot_payload' => $result['meta'] ?? $result,
            'commentorable_id' => $chatBot->id,
            'commentorable_type' => PRFMorphType::CHAT_BOT,
        ]);
    }

    public function backoff(): array
    {
        return [10, 20, 30];
    }
}
