<?php

namespace Database\Factories;

use App\Models\DebriefNote;
use App\Models\Mission;
use Database\Factories\Concerns\ReusesExistingRecords;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DebriefNote>
 */
class DebriefNoteFactory extends Factory
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
            'note' => $this->faker->text(),
        ];
    }
}
