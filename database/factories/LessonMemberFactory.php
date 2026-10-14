<?php

namespace Database\Factories;

use App\Enums\PRFCompletionStatus;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonMember;
use App\Models\Member;
use App\Models\Module;
use Database\Factories\Concerns\ReusesExistingRecords;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LessonMember>
 */
class LessonMemberFactory extends Factory
{
    use ReusesExistingRecords;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_id' => $this->existingOrNew(Course::class),
            'module_id' => $this->existingOrNew(Module::class),
            'lesson_id' => $this->existingOrNew(Lesson::class),
            'member_id' => $this->existingOrNew(Member::class),
            'completion_status' => PRFCompletionStatus::cases()[0],
        ];
    }
}
