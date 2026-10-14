<?php

namespace Database\Factories;

use App\Models\Mission;
use App\Models\MissionType;
use App\Models\School;
use App\Models\SchoolTerm;
use App\States\Mission\Pending;
use Database\Factories\Concerns\ReusesExistingRecords;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Mission>
 */
class MissionFactory extends Factory
{
    use ReusesExistingRecords;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = Carbon::today()->addDays(2);

        return [
            'school_term_id' => $this->existingOrNew(SchoolTerm::class),
            'mission_type_id' => $this->existingOrNew(MissionType::class),
            'school_id' => $this->existingOrNew(School::class),
            'start_date' => $startDate,
            'start_time' => $this->faker->time('H:i'),
            'end_date' => Carbon::parse($startDate)->addDays($this->faker->numberBetween(0, 2)),
            'end_time' => $this->faker->time('H:i'),
            'mission_prep_notes' => $this->faker->text(),
            'capacity' => $this->faker->numberBetween(1, 12),
            'status' => Pending::class,
        ];
    }
}
