<?php

namespace Database\Factories;

use App\Models\MissionFaq;
use App\Models\Student;
use App\Models\StudentEnquiry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentEnquiry>
 */
class StudentEnquiryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::query()->inRandomOrder()->first()->getKey(),
            'mission_faq_id' => MissionFaq::query()->inRandomOrder()->first()?->getKey(),
            'content' => $this->faker->paragraph(),
        ];
    }
}
