<?php

namespace App\Jobs\CohortLetter;

use App\Models\Cohort;
use App\Models\CohortLetter;
use App\Models\Letter;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateJob
{
    use Dispatchable;

    public function __construct(
        public array $data,
    ) {}

    public function handle(): CohortLetter
    {
        $cohort = Cohort::query()->where('ulid', $this->data['cohort_ulid'])->firstOrFail();
        $letter = Letter::query()->where('ulid', $this->data['letter_ulid'])->firstOrFail();

        return CohortLetter::create([
            'cohort_id' => $cohort->id,
            'letter_id' => $letter->id,
        ]);
    }
}
