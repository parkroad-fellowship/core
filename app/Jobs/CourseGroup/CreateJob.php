<?php

namespace App\Jobs\CourseGroup;

use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\Group;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateJob
{
    use Dispatchable;

    public function __construct(
        public array $data,
    ) {}

    public function handle(): CourseGroup
    {
        $group = Group::query()->where('ulid', $this->data['group_ulid'])->firstOrFail();
        $course = Course::query()->where('ulid', $this->data['course_ulid'])->firstOrFail();

        return CourseGroup::create([
            'group_id' => $group->id,
            'course_id' => $course->id,
            'start_date' => $this->data['start_date'],
        ]);
    }
}
