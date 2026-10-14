<?php

namespace App\Observers;

use App\Events\StudentEnquiryReply\StudentEnquiryReplyCreated;
use App\Models\StudentEnquiryReply;

/**
 * Translates StudentEnquiryReply lifecycle changes into domain events; side effects live in listeners.
 */
class StudentEnquiryReplyObserver
{
    public function created(StudentEnquiryReply $studentEnquiryReply): void
    {
        StudentEnquiryReplyCreated::dispatch($studentEnquiryReply);
    }
}
