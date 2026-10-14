<?php

namespace App\Jobs\Course;

use App\Models\Course;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateJob
{
    use Dispatchable;

    /**
     * @param  array<mixed>  $data  form or request input; only named fields are used
     */
    public function __construct(
        public array $data,
        public string $ulid,
    ) {}

    public function handle(): Course
    {
        $course = Course::query()->withTrashed()->where('ulid', $this->ulid)->firstOrFail();
        $course->update(array_filter($this->data, is_string(...), ARRAY_FILTER_USE_KEY));

        return $course;
    }
}
