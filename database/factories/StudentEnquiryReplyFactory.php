<?php

namespace Database\Factories;

use App\Enums\PRFMorphType;
use App\Models\Member;
use App\Models\StudentEnquiry;
use App\Models\StudentEnquiryReply;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentEnquiryReply>
 */
class StudentEnquiryReplyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_enquiry_id' => StudentEnquiry::query()->inRandomOrder()->first()->getKey(),
            'commentorable_type' => PRFMorphType::MEMBER,
            'commentorable_id' => Member::query()->inRandomOrder()->first()->getKey(),
            'content' => $this->faker->paragraph(),

        ];
    }
}
