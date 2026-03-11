<?php

namespace Database\Factories;

use App\Models\APIClient;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<APIClient>
 */
class APIClientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->company().' App',
            'app_id' => 'app-'.$this->faker->unique()->slug(2),
            'secret' => Str::random(64),
            'is_active' => true,
            'allowed_roles' => [],
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function withAllowedRoles(array $roles): static
    {
        return $this->state(fn (array $attributes) => [
            'allowed_roles' => $roles,
        ]);
    }
}
