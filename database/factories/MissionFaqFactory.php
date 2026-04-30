<?php

namespace Database\Factories;

use App\Models\MissionFaq;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MissionFaq>
 */
class MissionFaqFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'question' => $this->faker->text(),
            'answer' => $this->faker->paragraph(),
        ];
    }
}
