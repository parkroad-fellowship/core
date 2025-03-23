<?php

namespace App\Observers;

use App\Events\MemberModule\Updated;
use App\Jobs\MemberModule\NotifyProgressJob;
use App\Models\CourseMember;
use App\Models\MemberModule;

class MemberModuleObserver
{
    /**
     * Handle the MemberModule "created" event.
     */
    public function created(MemberModule $memberModule): void
    {
        CourseMember::updateOrCreate(
            [
                'course_id' => $memberModule->course_id,
                'member_id' => $memberModule->member_id,
            ],
            [
                'course_id' => $memberModule->course_id,
                'member_id' => $memberModule->member_id,
            ],
        );
    }

    /**
     * Handle the MemberModule "updated" event.
     */
    public function updated(MemberModule $memberModule): void
    {
        NotifyProgressJob::dispatch($memberModule);
    }

    /**
     * Handle the MemberModule "deleted" event.
     */
    public function deleted(MemberModule $memberModule): void
    {
        //
    }

    /**
     * Handle the MemberModule "restored" event.
     */
    public function restored(MemberModule $memberModule): void
    {
        //
    }

    /**
     * Handle the MemberModule "force deleted" event.
     */
    public function forceDeleted(MemberModule $memberModule): void
    {
        //
    }
}
