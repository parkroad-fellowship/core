<?php

namespace App\Jobs\Lesson;

use App\Models\Lesson;
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

    public function handle(): Lesson
    {
        $lesson = Lesson::query()->withTrashed()->where('ulid', $this->ulid)->firstOrFail();
        $lesson->update(array_filter($this->data, is_string(...), ARRAY_FILTER_USE_KEY));

        return $lesson;
    }
}
