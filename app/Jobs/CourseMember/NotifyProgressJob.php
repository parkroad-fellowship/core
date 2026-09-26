<?php

namespace App\Jobs\CourseMember;

use App\Events\CourseMember\Updated;
use App\Http\Resources\Course\Resource;
use App\Models\Course;
use App\Models\CourseMember;
use App\Models\Member;
use App\Models\User;
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
        public CourseMember $courseMember,
    ) {}

    public function handle(): void
    {
        $courseMember = $this->courseMember;

        $course = Course::query()->where('id', $courseMember->course_id)->with(['thumbnail'])->firstOrFail();

        $course->setRelation('courseMember', $courseMember);

        $user = User::query()
            ->where('id', Member::query()->where('id', $courseMember->member_id)->select('user_id')->limit(1))
            ->firstOrFail();

        Updated::dispatch(new Resource($course), $user->ulid);
    }
}
