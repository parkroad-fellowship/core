<?php

namespace App\Jobs\CourseGroup;

use App\Jobs\Concerns\ResolvesULIDs;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\Group;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateJob
{
    use Dispatchable;
    use ResolvesULIDs;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public array $data,
        public string $ulid,
    ) {}

    public function handle(): CourseGroup
    {
        $courseGroup = CourseGroup::query()->where('ulid', $this->ulid)->firstOrFail();

        $attributes = $this->resolveULIDs($this->data, [
            'group_ulid' => Group::class,
            'course_ulid' => Course::class,
        ]);

        $courseGroup->update($attributes);

        return $courseGroup;
    }
}
