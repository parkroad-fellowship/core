<?php

namespace App\Observers;

use App\Enums\PRFCompletionStatus;
use App\Events\LessonMember\Created;
use App\Http\Resources\LessonMember\Resource;
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
        $memberModule = MemberModule::updateOrCreate(
            [
                'course_id' => $lessonMember->course_id,
                'module_id' => $lessonMember->module_id,
                'member_id' => $lessonMember->member_id,
            ],
            [
                'course_id' => $lessonMember->course_id,
                'module_id' => $lessonMember->module_id,
                'member_id' => $lessonMember->member_id,
            ],
        );

        $completedLessonsInModule = LessonMember::query()
            ->where([
                'course_id' => $lessonMember->course_id,
                'module_id' => $lessonMember->module_id,
                'member_id' => $lessonMember->member_id,
                'completion_status' => PRFCompletionStatus::COMPLETE,
            ])
            ->count();

        $lessonsInModule = LessonModule::query()
            ->where('module_id', $lessonMember->module_id)
            ->count();

        $percentComplete = ($completedLessonsInModule / $lessonsInModule);

        $memberModule->update(
            [
                'percent_complete' => $percentComplete * 100,
                'completion_status' => match ($percentComplete) {
                    1 => PRFCompletionStatus::COMPLETE,
                    default => PRFCompletionStatus::INCOMPLETE,
                },
                'completed_at' => $percentComplete === 1 ? now() : null,
            ],
        );

        $user = User::query()
            ->where('id', Member::query()
                ->where('id', $memberModule->member_id)
                ->select('id')
                ->limit(1))
            ->firstOrFail();

        $lessonMember->load(['course', 'module']);

        Created::dispatch(
            new Resource($lessonMember),
            $user->ulid,
        );
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
