<?php

namespace App\Jobs\MemberModule;

use App\Enums\PRFCompletionStatus;
use App\Events\MemberModule\Updated;
use App\Http\Resources\CourseModule\Resource;
use App\Models\CourseMember;
use App\Models\CourseModule;
use App\Models\Member;
use App\Models\MemberModule;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class NotifyProgressJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public MemberModule $memberModule,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $memberModule = $this->memberModule;

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
                ->select('user_id')
                ->limit(1))
            ->firstOrFail();

        $courseModule = CourseModule::query()
            ->where([
                'course_id' => $memberModule->course_id,
                'module_id' => $memberModule->module_id,
            ])
            ->with([
                'course.thumbnail',
                'memberModule',
                'module',
            ])
            ->firstOrFail();

        Updated::dispatch(
            new Resource($courseModule),
            $user->ulid,
        );
    }
}
