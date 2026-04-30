<?php

namespace Database\Factories;

use App\Enums\PRFActiveStatus;
use App\Models\Module;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Module>
 */
class ModuleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence,
            'description' => $this->faker->paragraph,
            'is_active' => $this->faker->randomElement(PRFActiveStatus::getElements()),
        ];
    }
}
