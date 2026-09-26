<?php

namespace Database\Factories;

use App\Models\ClassGroup;
use App\Models\Mission;
use App\Models\Soul;
use Database\Factories\Concerns\ReusesExistingRecords;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Soul>
 */
class SoulFactory extends Factory
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
            'mission_id' => $this->existingOrNew(Mission::class),
            'class_group_id' => $this->existingOrNew(ClassGroup::class),
            'full_name' => $this->faker->name,
        ];
    }
}
