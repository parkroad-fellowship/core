<?php

namespace Database\Factories;

use App\Enums\PRFMissionGroundSuggestionStatus;
use App\Models\Member;
use App\Models\MissionGroundSuggestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MissionGroundSuggestion>
 */
class MissionGroundSuggestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'suggestor_id' => Member::query()->inRandomOrder()->first()->getKey(),
            'name' => $this->faker->company(),
            'contact_person' => $this->faker->name(),
            'contact_number' => $this->faker->e164PhoneNumber(),
            'status' => PRFMissionGroundSuggestionStatus::PENDING->value,
            'notes' => 'N/A',
        ];
    }
}
