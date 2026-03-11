<?php

namespace Database\Factories;

use App\Enums\PRFActiveStatus;
use App\Models\Letter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Letter>
 */
class LetterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'is_active' => $this->faker->randomElement(PRFActiveStatus::getElements()),
            'content' => $this->faker->randomHtml(),
        ];
    }
}
