<?php

namespace App\Listeners\StudentEnquiry;

use App\Events\StudentEnquiry\StudentEnquiryCreated;
use App\Jobs\StudentEnquiry\AskChatBotJob;

class AskChatBotAboutEnquiry
{
    public function handle(StudentEnquiryCreated $event): void
    {
        AskChatBotJob::dispatch(enquiryId: $event->studentEnquiry->id, content: $event->studentEnquiry->content);
    }
}
