<?php

namespace App\Observers;

use App\Events\CourseMember\Updated;
use App\Http\Resources\Course\Resource;
use App\Models\Course;
use App\Models\CourseMember;
use App\Models\Member;
use App\Models\User;

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

        $user = User::query()
            ->where('id', Member::query()
                ->where('id', $courseMember->member_id)
                ->select('id')
                ->limit(1))
            ->firstOrFail();

        Updated::dispatch(
            new Resource($course),
            $user->ulid,
        );
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
