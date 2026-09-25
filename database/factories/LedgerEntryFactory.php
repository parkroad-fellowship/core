<?php

namespace Database\Factories;

use App\Enums\PRFLedgerChannel;
use App\Enums\PRFLedgerFlow;
use App\Models\FinancialAccount;
use App\Models\LedgerCategory;
use App\Models\LedgerEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LedgerEntry>
 */
class LedgerEntryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'financial_account_id' => FinancialAccount::factory(),
            'ledger_category_id' => LedgerCategory::factory()->income(),
            'flow' => PRFLedgerFlow::RECEIPT,
            'channel' => PRFLedgerChannel::PAYBILL,
            'amount' => $this->faker->numberBetween(100, 20_000),
            'transacted_on' => $this->faker->dateTimeBetween('-2 months'),
            'counterparty' => $this->faker->name(),
            'description' => $this->faker->sentence(3),
            'reference' => strtoupper($this->faker->bothify('??##??##??')),
        ];
    }

    public function payment(): static
    {
        return $this->state(fn() => [
            'flow' => PRFLedgerFlow::PAYMENT,
            'ledger_category_id' => LedgerCategory::factory()->expense(),
        ]);
    }
}
