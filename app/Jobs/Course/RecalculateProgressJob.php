<?php

namespace App\Jobs\Course;

use App\Models\Course;
use App\Models\CourseMember;
use App\Models\MemberModule;
use App\Services\ELearning\CourseProgress;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Queue\Attributes\Tries;

/**
 * Recomputes every enrolled member's module and course percentages after the curriculum changed.
 * Writes quietly: the learner did nothing, so no progress broadcast or activity entry is due.
 */
#[Queue('default')]
#[Tries(3)]
class RecalculateProgressJob implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public function __construct(
        public Course $course,
    ) {}

    public function uniqueId(): string
    {
        return $this->course->ulid;
    }

    public function handle(CourseProgress $progress): void
    {
        CourseMember::query()
            ->where('course_id', $this->course->id)
            ->lazyById()
            ->each(function (CourseMember $courseMember) use ($progress): void {
                MemberModule::query()
                    ->where(['course_id' => $courseMember->course_id, 'member_id' => $courseMember->member_id])
                    ->get()
                    ->each(fn(MemberModule $memberModule) => $memberModule->updateQuietly(CourseProgress::attributes(
                        $progress->module($memberModule->course_id, $memberModule->module_id, $memberModule->member_id),
                        $memberModule->completed_at,
                    )));

                $courseMember->updateQuietly(CourseProgress::attributes(
                    $progress->course($courseMember->course_id, $courseMember->member_id),
                    $courseMember->completed_at,
                ));
            });
    }
}
