<?php

namespace App\Observers;

use App\Models\Course;
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
        $course = Course::query()
            ->where('id', $courseMember->course_id)
            ->with(['thumbnail'])
            ->firstOrFail();

        $course->setRelation('courseMember', $courseMember);
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
