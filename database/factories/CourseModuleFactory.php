<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Module;
use Database\Factories\Concerns\ReusesExistingRecords;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseModule>
 */
class CourseModuleFactory extends Factory
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
            'order' => $this->faker->numberBetween(1, 10),
        ];
    }
}
