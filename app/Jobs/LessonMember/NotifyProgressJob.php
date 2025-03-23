<?php

namespace App\Jobs\LessonMember;

use App\Enums\PRFCompletionStatus;
use App\Events\LessonMember\Created;
use App\Http\Resources\LessonModule\Resource;
use App\Models\LessonMember;
use App\Models\LessonModule;
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
        public LessonMember $lessonMember,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $lessonMember = $this->lessonMember;

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
                ->select('user_id')
                ->limit(1))
            ->firstOrFail();

        $lessonMember->load(['course', 'module', 'lesson']);

        $lessonModule = LessonModule::query()
            ->where([
                'lesson_id' => $lessonMember->lesson_id,
                'module_id' => $lessonMember->module_id,
            ])
            ->first();

        $lessonModule
            ->load(['lesson', 'module'])
            ->setRelation('lessonMember', $lessonMember);

        Created::dispatch(
            new Resource($lessonModule),
            $user->ulid,
        );
    }
}
