<?php

namespace App\Events\StudentEnquiryReply;

use App\Models\StudentEnquiryReply;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Someone replied to a student enquiry.
 */
class StudentEnquiryReplyCreated implements ShouldDispatchAfterCommit
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public StudentEnquiryReply $studentEnquiryReply,
    ) {}
}
