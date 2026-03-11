<?php

namespace Database\Factories;

use App\Models\MissionFaqCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MissionFaqCategory>
 */
class MissionFaqCategoryFactory extends Factory
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
