<?php

namespace Database\Factories;

use App\Models\MissionType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MissionType>
 */
class MissionTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
        ];
    }
}
