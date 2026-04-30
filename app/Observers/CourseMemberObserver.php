<?php

namespace App\Observers;

use App\Events\CourseMember\Updated;
use App\Jobs\CourseMember\NotifyProgressJob;
use App\Models\CourseMember;

class CourseMemberObserver
{
    /**
     * Handle the CourseMember "created" event.
     */
    public function created(CourseMember $courseMember): void
    {
        //
    }

    /**
     * Handle the CourseMember "updated" event.
     */
    public function updated(CourseMember $courseMember): void
    {
        NotifyProgressJob::dispatch($courseMember);
    }

    /**
     * Handle the CourseMember "deleted" event.
     */
    public function deleted(CourseMember $courseMember): void
    {
        //
    }

    /**
     * Handle the CourseMember "restored" event.
     */
    public function restored(CourseMember $courseMember): void
    {
        //
    }

    /**
     * Handle the CourseMember "force deleted" event.
     */
    public function forceDeleted(CourseMember $courseMember): void
    {
        //
    }
}
