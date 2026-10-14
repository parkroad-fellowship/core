<?php

namespace App\Listeners\StudentEnquiryReply;

use App\Events\StudentEnquiryReply\Created;
use App\Events\StudentEnquiryReply\StudentEnquiryReplyCreated;

class BroadcastStudentEnquiryReply
{
    public function handle(StudentEnquiryReplyCreated $event): void
    {
        $reply = $event->studentEnquiryReply->load('studentEnquiry');

        Created::dispatch(new \App\Http\Resources\StudentEnquiryReply\Resource($reply), $reply->studentEnquiry->ulid);
    }
}
