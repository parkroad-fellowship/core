<?php

namespace Database\Factories;

use App\Enums\PRFLedgerCategoryKind;
use App\Enums\PRFResponsibleDesk;
use App\Models\LedgerCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LedgerCategory>
 */
class LedgerCategoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(3, true),
            'kind' => PRFLedgerCategoryKind::INCOME,
            'responsible_desk' => null,
            'sort' => 0,
            'is_active' => true,
        ];
    }

    public function income(): static
    {
        return $this->state(fn() => ['kind' => PRFLedgerCategoryKind::INCOME, 'responsible_desk' => null]);
    }

    public function expense(PRFResponsibleDesk $desk = PRFResponsibleDesk::MISSIONS_DESK): static
    {
        return $this->state(fn() => ['kind' => PRFLedgerCategoryKind::EXPENSE, 'responsible_desk' => $desk]);
    }

    public function refund(PRFResponsibleDesk $desk = PRFResponsibleDesk::MISSIONS_DESK): static
    {
        return $this->state(fn() => ['kind' => PRFLedgerCategoryKind::REFUND, 'responsible_desk' => $desk]);
    }

    public function charge(): static
    {
        return $this->state(fn() => [
            'kind' => PRFLedgerCategoryKind::CHARGE,
            'responsible_desk' => PRFResponsibleDesk::TREASURER_DESK,
        ]);
    }
}
