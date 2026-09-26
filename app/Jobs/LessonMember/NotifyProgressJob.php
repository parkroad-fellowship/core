<?php

namespace App\Jobs\LessonMember;

use App\Events\LessonMember\Created;
use App\Http\Resources\LessonModule\Resource;
use App\Models\LessonMember;
use App\Models\LessonModule;
use App\Models\Member;
use App\Models\MemberModule;
use App\Models\User;
use App\Services\ELearning\CourseProgress;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Queue\Attributes\Tries;

#[Queue('default')]
#[Tries(3)]
class NotifyProgressJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public LessonMember $lessonMember,
    ) {}

    public function handle(CourseProgress $progress): void
    {
        $lessonMember = $this->lessonMember;

        $memberModule = MemberModule::updateOrCreate([
            'course_id' => $lessonMember->course_id,
            'module_id' => $lessonMember->module_id,
            'member_id' => $lessonMember->member_id,
        ], [
            'course_id' => $lessonMember->course_id,
            'module_id' => $lessonMember->module_id,
            'member_id' => $lessonMember->member_id,
        ]);

        $memberModule->update(CourseProgress::attributes(
            $progress->module($lessonMember->course_id, $lessonMember->module_id, $lessonMember->member_id),
            $memberModule->completed_at,
        ));

        $user = User::query()
            ->where('id', Member::query()->where('id', $memberModule->member_id)->select('user_id')->limit(1))
            ->firstOrFail();

        $lessonMember->load(['course', 'module', 'lesson']);

        $lessonModule = LessonModule::query()
            ->where([
                'lesson_id' => $lessonMember->lesson_id,
                'module_id' => $lessonMember->module_id,
            ])
            ->first();

        if ($lessonModule === null) {
            return;
        }

        $lessonModule->load(['lesson', 'module'])->setRelation('lessonMember', $lessonMember);

        Created::dispatch(new Resource($lessonModule), $user->ulid);
    }
}
