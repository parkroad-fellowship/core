<?php

namespace App\Jobs\CohortMission;

use App\Models\Cohort;
use App\Models\CohortMission;
use App\Models\Mission;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateJob
{
    use Dispatchable;

    public function __construct(
        public array $data,
    ) {}

    public function handle(): CohortMission
    {
        $cohort = Cohort::query()->where('ulid', $this->data['cohort_ulid'])->firstOrFail();
        $mission = Mission::query()->where('ulid', $this->data['mission_ulid'])->firstOrFail();

        return CohortMission::create([
            'cohort_id' => $cohort->id,
            'mission_id' => $mission->id,
        ]);
    }
}
