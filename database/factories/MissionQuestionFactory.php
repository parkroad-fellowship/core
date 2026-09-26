<?php

namespace Database\Factories;

use App\Models\Mission;
use App\Models\MissionQuestion;
use Database\Factories\Concerns\ReusesExistingRecords;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MissionQuestion>
 */
class MissionQuestionFactory extends Factory
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
            'question' => $this->faker->text(),
        ];
    }
}
