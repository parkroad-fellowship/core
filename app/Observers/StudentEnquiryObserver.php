<?php

namespace App\Observers;

use App\Jobs\StudentEnquiry\AskChatBotJob;
use App\Jobs\StudentEnquiry\NotifyMembersJob;
use App\Models\StudentEnquiry;

class StudentEnquiryObserver
{
    /**
     * Handle the StudentEnquiry "created" event.
     */
    public function created(StudentEnquiry $studentEnquiry): void
    {
        AskChatBotJob::dispatch(
            enquiryId: $studentEnquiry->id,
            content: $studentEnquiry->content,
        );
        NotifyMembersJob::dispatch($studentEnquiry);
    }

    /**
     * Handle the StudentEnquiry "updated" event.
     */
    public function updated(StudentEnquiry $studentEnquiry): void
    {
        //
    }

    /**
     * Handle the StudentEnquiry "deleted" event.
     */
    public function deleted(StudentEnquiry $studentEnquiry): void
    {
        //
    }

    /**
     * Handle the StudentEnquiry "restored" event.
     */
    public function restored(StudentEnquiry $studentEnquiry): void
    {
        //
    }

    /**
     * Handle the StudentEnquiry "force deleted" event.
     */
    public function forceDeleted(StudentEnquiry $studentEnquiry): void
    {
        //
    }
}
