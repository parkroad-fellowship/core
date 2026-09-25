<?php

namespace Database\Factories;

use App\Enums\PRFFinancialAccountType;
use App\Models\FinancialAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinancialAccount>
 */
class FinancialAccountFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->company() . ' Account',
            'type' => PRFFinancialAccountType::BANK,
            'identifier' => (string) $this->faker->numerify('##########'),
            'is_active' => true,
        ];
    }

    public function ofType(PRFFinancialAccountType $type): static
    {
        return $this->state(fn() => [
            'type' => $type,
            'name' => $type->getLabel() . ' ' . $this->faker->unique()->numerify('###'),
        ]);
    }
}
