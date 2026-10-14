<?php

namespace App\Jobs\School;

use App\Models\School;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateJob
{
    use Dispatchable;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public array $data,
        public string $ulid,
    ) {}

    public function handle(): School
    {
        $school = School::query()->where('ulid', $this->ulid)->firstOrFail();

        $attributes = $this->data;

        $school->update($attributes);

        return $school;
    }
}
