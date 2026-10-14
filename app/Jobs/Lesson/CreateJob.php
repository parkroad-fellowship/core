<?php

namespace App\Jobs\Lesson;

use App\Models\Lesson;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateJob
{
    use Dispatchable;

    /**
     * @param  array<mixed>  $data  form or request input; only named fields are used
     */
    public function __construct(
        public array $data,
    ) {}

    public function handle(): Lesson
    {
        return Lesson::query()->create(array_filter($this->data, is_string(...), ARRAY_FILTER_USE_KEY));
    }
}
