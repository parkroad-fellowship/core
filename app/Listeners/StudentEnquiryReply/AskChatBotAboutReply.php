<?php

namespace App\Listeners\StudentEnquiryReply;

use App\Enums\PRFMorphType;
use App\Events\StudentEnquiryReply\StudentEnquiryReplyCreated;
use App\Jobs\StudentEnquiry\AskChatBotJob;

class AskChatBotAboutReply
{
    public function handle(StudentEnquiryReplyCreated $event): void
    {
        $reply = $event->studentEnquiryReply;

        // Only student replies go to the chatbot: never its own answers or a member's.
        if ($reply->is_from_chat_bot || $reply->commentorable_type === PRFMorphType::MEMBER) {
            return;
        }

        AskChatBotJob::dispatch(enquiryId: $reply->student_enquiry_id, content: $reply->content);
    }
}
