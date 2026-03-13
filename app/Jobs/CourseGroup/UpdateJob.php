<?php

namespace App\Jobs\CourseGroup;

use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\Group;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateJob
{
    use Dispatchable;

    public function __construct(
        public array $data,
        public string $ulid,
    ) {}

    public function handle(): void
    {
        $update = $this->data;

        if (isset($update['group_ulid'])) {
            $group = Group::query()->where('ulid', $update['group_ulid'])->firstOrFail();
            $update['group_id'] = $group->id;
            unset($update['group_ulid']);
        }

        if (isset($update['course_ulid'])) {
            $course = Course::query()->where('ulid', $update['course_ulid'])->firstOrFail();
            $update['course_id'] = $course->id;
            unset($update['course_ulid']);
        }

        CourseGroup::query()
            ->where('ulid', $this->ulid)
            ->firstOrFail()
            ->update($update);
    }
}
