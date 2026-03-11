<?php

namespace Database\Factories;

use App\Models\Mission;
use App\Models\MissionQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MissionQuestion>
 */
class MissionQuestionFactory extends Factory
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
            'question' => $this->faker->text(),
        ];
    }
}
