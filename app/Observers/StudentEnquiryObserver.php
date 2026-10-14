<?php

namespace App\Observers;

use App\Events\StudentEnquiry\StudentEnquiryCreated;
use App\Models\StudentEnquiry;

/**
 * Translates StudentEnquiry lifecycle changes into domain events; side effects live in listeners.
 */
class StudentEnquiryObserver
{
    public function created(StudentEnquiry $studentEnquiry): void
    {
        StudentEnquiryCreated::dispatch($studentEnquiry);
    }
}
