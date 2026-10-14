<?php

namespace App\Events\StudentEnquiry;

use App\Models\StudentEnquiry;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A student asked a question.
 */
class StudentEnquiryCreated implements ShouldDispatchAfterCommit
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public StudentEnquiry $studentEnquiry,
    ) {}
}
