<?php

namespace Database\Factories;

use App\Models\MissionType;
use App\Models\School;
use App\Models\SchoolTerm;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Mission>
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
        $startDate = $this->faker->date();

        return [
            'school_term_id' => SchoolTerm::query()->inRandomOrder()->first()->getKey(),
            'mission_type_id' => MissionType::query()->inRandomOrder()->first()->getKey(),
            'school_id' => School::query()->inRandomOrder()->first()->getKey(),
            'start_date' => $startDate,
            'end_date' => Carbon::parse($startDate)->addDays($this->faker->numberBetween(0, 2)),
            'mission_prep_notes' => $this->faker->text(),
            'capacity' => $this->faker->numberBetween(1, 12),
        ];
    }
}
