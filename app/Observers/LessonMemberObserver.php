<?php

namespace App\Observers;

use App\Enums\PRFCompletionStatus;
use App\Events\LessonMember\Created;
use App\Http\Resources\LessonModule\Resource;
use App\Jobs\LessonMember\NotifyProgressJob;
use App\Models\LessonMember;
use App\Models\LessonModule;
use App\Models\Member;
use App\Models\MemberModule;
use App\Models\User;

class LessonMemberObserver
{
    /**
     * Handle the LessonMember "created" event.
     */
    public function created(LessonMember $lessonMember): void
    {
        NotifyProgressJob::dispatch($lessonMember);
    }

    /**
     * Handle the LessonMember "updated" event.
     */
    public function updated(LessonMember $lessonMember): void
    {
        //
    }

    /**
     * Handle the LessonMember "deleted" event.
     */
    public function deleted(LessonMember $lessonMember): void
    {
        //
    }

    /**
     * Handle the LessonMember "restored" event.
     */
    public function restored(LessonMember $lessonMember): void
    {
        //
    }

    /**
     * Handle the LessonMember "force deleted" event.
     */
    public function forceDeleted(LessonMember $lessonMember): void
    {
        //
    }
}
