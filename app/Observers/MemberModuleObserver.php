<?php

namespace App\Observers;

use App\Enums\PRFCompletionStatus;
use App\Events\MemberModule\Updated;
use App\Http\Resources\CourseModule\Resource;
use App\Models\CourseMember;
use App\Models\CourseModule;
use App\Models\Member;
use App\Models\MemberModule;
use App\Models\User;

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
        $courseMember = CourseMember::query()
            ->where([
                'course_id' => $memberModule->course_id,
                'member_id' => $memberModule->member_id,
            ])
            ->firstOrFail();

        $completedModulesInCourse = MemberModule::query()
            ->where([
                'course_id' => $courseMember->course_id,
                'member_id' => $courseMember->member_id,
                'completion_status' => PRFCompletionStatus::COMPLETE,
            ])
            ->count();

        $modulesInCourse = CourseModule::query()
            ->where('course_id', $courseMember->course_id)
            ->count();

        $percentComplete = ($completedModulesInCourse / $modulesInCourse);

        $courseMember->update(
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


        $courseModule = CourseModule::query()
            ->where([
                'course_id' => $memberModule->course_id,
                'module_id' => $memberModule->module_id,
            ])
            ->with([
                'course.thumbnail',
                'module.thumbnail',
                'module.memberModule',
                'module.lessonModules.lesson',
                'module.lessonModules.lessonMember',
            ])
            ->firstOrFail();


        $courseModule->setRelation('course.courseMember', $courseMember);

        Updated::dispatch(
            new Resource($courseModule),
            $user->ulid,
        );
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
