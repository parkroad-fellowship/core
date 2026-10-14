<?php

namespace Database\Factories;

use App\Models\Cohort;
use App\Models\CohortLetter;
use App\Models\Letter;
use Database\Factories\Concerns\ReusesExistingRecords;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CohortLetter>
 */
class CohortLetterFactory extends Factory
{
    use ReusesExistingRecords;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cohort_id' => $this->existingOrNew(Cohort::class),
            'letter_id' => $this->existingOrNew(Letter::class),
        ];
    }
}
