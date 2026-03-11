<?php

namespace Database\Factories;

use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<School>
 */
class SchoolFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'description' => $this->faker->text,
            'total_students' => $this->faker->numberBetween(0, 100),
            'address' => $this->faker->address,
            'directions' => $this->faker->text,
            // A school in Nairobi
            'latitude' => '-1.2788743',
            'longitude' => '36.7006562',
            'mission_defaults' => null,
        ];
    }

    public function withMissionDefaults(array $defaults = []): static
    {
        return $this->state(fn (array $attributes) => [
            'mission_defaults' => array_merge([
                'default_start_time' => '08:00',
                'default_end_time' => '15:00',
                'default_capacity' => 10,
                'default_mission_type_id' => null,
            ], $defaults),
        ]);
    }
}
