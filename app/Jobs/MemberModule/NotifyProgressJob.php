<?php

namespace App\Jobs\MemberModule;

use App\Events\MemberModule\Updated;
use App\Http\Resources\CourseModule\Resource;
use App\Models\CourseMember;
use App\Models\CourseModule;
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
        public MemberModule $memberModule,
    ) {}

    public function handle(CourseProgress $progress): void
    {
        $memberModule = $this->memberModule;

        $courseMember = CourseMember::query()
            ->where([
                'course_id' => $memberModule->course_id,
                'member_id' => $memberModule->member_id,
            ])
            ->first();

        // Progress can arrive for a member who was never enrolled (e.g. enrolment removed).
        if ($courseMember === null) {
            return;
        }

        $courseMember->update(CourseProgress::attributes(
            $progress->course($courseMember->course_id, $courseMember->member_id),
            $courseMember->completed_at,
        ));

        $user = User::query()
            ->where('id', Member::query()->where('id', $memberModule->member_id)->select('user_id')->limit(1))
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
            ->first();

        if ($courseModule === null) {
            return;
        }

        Updated::dispatch(new Resource($courseModule), $user->ulid);
    }
}
