<?php

namespace App\Jobs\Cohort;

use App\Models\Cohort;
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

    public function handle(): Cohort
    {
        $cohort = Cohort::query()->where('ulid', $this->ulid)->firstOrFail();

        $attributes = $this->data;

        $cohort->update($attributes);

        return $cohort;
    }
}
