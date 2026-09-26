<?php

namespace Database\Factories;

use App\Enums\PRFMorphType;
use App\Models\Member;
use App\Models\StudentEnquiry;
use App\Models\StudentEnquiryReply;
use Database\Factories\Concerns\ReusesExistingRecords;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentEnquiryReply>
 */
class StudentEnquiryReplyFactory extends Factory
{
    use ReusesExistingRecords;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_enquiry_id' => $this->existingOrNew(StudentEnquiry::class),
            'commentorable_type' => PRFMorphType::MEMBER,
            'commentorable_id' => $this->existingOrNew(Member::class),
            'content' => $this->faker->paragraph(),
        ];
    }
}
