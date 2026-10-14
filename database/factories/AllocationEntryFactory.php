<?php

namespace Database\Factories;

use App\Enums\PRFEntryType;
use App\Models\AccountingEvent;
use App\Models\AllocationEntry;
use App\Models\ExpenseCategory;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AllocationEntry>
 */
class AllocationEntryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'accounting_event_id' => AccountingEvent::factory(),
            'expense_category_id' => ExpenseCategory::factory(),
            'member_id' => Member::factory(),
            'entry_type' => PRFEntryType::DEBIT,
            'amount' => 1_000,
            'unit_cost' => 1_000,
            'quantity' => 1,
            'charge' => 0,
            'narration' => $this->faker->sentence(3),
        ];
    }

    /**
     * Money disbursed to the event.
     */
    public function credit(int $amount): static
    {
        return $this->state(fn() => [
            'entry_type' => PRFEntryType::CREDIT,
            'expense_category_id' => null,
            'amount' => $amount,
            'unit_cost' => $amount,
        ]);
    }

    /**
     * Money spent on one expense category.
     */
    public function debit(int $amount, ?ExpenseCategory $category = null): static
    {
        return $this->state(fn() => [
            'entry_type' => PRFEntryType::DEBIT,
            'expense_category_id' => $category?->getKey() ?? ExpenseCategory::factory(),
            'amount' => $amount,
            'unit_cost' => $amount,
        ]);
    }

    public function token(int $amount): static
    {
        return $this->credit($amount)->state(fn() => ['is_token_of_appreciation' => true]);
    }
}
