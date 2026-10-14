<?php

namespace Database\Factories;

use App\Models\Cohort;
use App\Models\CohortMission;
use App\Models\Mission;
use Database\Factories\Concerns\ReusesExistingRecords;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CohortMission>
 */
class CohortMissionFactory extends Factory
{
    use ReusesExistingRecords;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cohort_id' => $this->existingOrNew(Cohort::class),
            'mission_id' => $this->existingOrNew(Mission::class),
        ];
    }
}
