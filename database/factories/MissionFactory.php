<?php

namespace Database\Factories;

use App\Enums\PRFMissionStatus;
use App\Models\Mission;
use App\Models\MissionType;
use App\Models\School;
use App\Models\SchoolTerm;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Mission>
 */
class MissionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = Carbon::today()->addDays(2);

        return [
            'school_term_id' => SchoolTerm::query()->inRandomOrder()->first()->getKey(),
            'mission_type_id' => MissionType::query()->inRandomOrder()->first()->getKey(),
            'school_id' => School::query()->inRandomOrder()->first()->getKey(),
            'start_date' => $startDate,
            'start_time' => $this->faker->time('H:i'),
            'end_date' => Carbon::parse($startDate)->addDays($this->faker->numberBetween(0, 2)),
            'end_time' => $this->faker->time('H:i'),
            'mission_prep_notes' => $this->faker->text(),
            'capacity' => $this->faker->numberBetween(1, 12),
            'status' => $this->faker->randomElement([PRFMissionStatus::PENDING]),
        ];
    }
}
