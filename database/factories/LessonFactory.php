<?php

namespace Database\Factories;

use App\Enums\PRFActiveStatus;
use App\Enums\PRFLessonType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Lesson>
 */
class LessonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'type' => $this->faker->randomElement(PRFLessonType::getElements()),
            'is_active' => $this->faker->randomElement(PRFActiveStatus::getElements()),

        ];
    }
}
