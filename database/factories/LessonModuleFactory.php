<?php

namespace Database\Factories;

use App\Models\Lesson;
use App\Models\LessonModule;
use App\Models\Module;
use Database\Factories\Concerns\ReusesExistingRecords;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LessonModule>
 */
class LessonModuleFactory extends Factory
{
    use ReusesExistingRecords;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lesson_id' => $this->existingOrNew(Lesson::class),
            'module_id' => $this->existingOrNew(Module::class),
            'order' => $this->faker->numberBetween(1, 10),
        ];
    }
}
