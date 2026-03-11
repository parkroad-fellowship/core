<?php

namespace Database\Factories;

use App\Models\ClassGroup;
use App\Models\Mission;
use App\Models\Soul;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Soul>
 */
class SoulFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'mission_id' => Mission::query()->inRandomOrder()->first()->getKey(),
            'class_group_id' => ClassGroup::query()->inRandomOrder()->first()->getKey(),
            'full_name' => $this->faker->name,
        ];
    }
}
