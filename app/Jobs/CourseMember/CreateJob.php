<?php

namespace App\Jobs\CourseMember;

use App\Models\Course;
use App\Models\CourseMember;
use App\Models\Member;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateJob
{
    use Dispatchable;

    public function __construct(
        public array $data,
    ) {}

    public function handle(): CourseMember
    {
        $course = Course::query()->where('ulid', $this->data['course_ulid'])->firstOrFail();
        $member = Member::query()->where('ulid', $this->data['member_ulid'])->firstOrFail();

        return CourseMember::create([
            'course_id' => $course->id,
            'member_id' => $member->id,
        ]);
    }
}
