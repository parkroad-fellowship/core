<?php

namespace App\Listeners\StudentEnquiryReply;

use App\Events\StudentEnquiryReply\StudentEnquiryReplyCreated;
use App\Jobs\StudentEnquiryReply\NotifyParticipantsJob;

class NotifyEnquiryParticipants
{
    public function handle(StudentEnquiryReplyCreated $event): void
    {
        NotifyParticipantsJob::dispatch($event->studentEnquiryReply);
    }
}
