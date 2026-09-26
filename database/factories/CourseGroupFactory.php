<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\Group;
use Database\Factories\Concerns\ReusesExistingRecords;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseGroup>
 */
class CourseGroupFactory extends Factory
{
    use ReusesExistingRecords;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'group_id' => $this->existingOrNew(Group::class),
            'course_id' => $this->existingOrNew(Course::class),
            'start_date' => now()->toDateString(),
        ];
    }
}
