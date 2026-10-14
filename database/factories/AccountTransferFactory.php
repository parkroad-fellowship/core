<?php

namespace Database\Factories;

use App\Models\AccountTransfer;
use App\Models\FinancialAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccountTransfer>
 */
class AccountTransferFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'from_financial_account_id' => FinancialAccount::factory(),
            'to_financial_account_id' => FinancialAccount::factory(),
            'amount' => $this->faker->numberBetween(1_000, 100_000),
            'charge' => 0,
            'transferred_on' => $this->faker->dateTimeBetween('-1 month'),
            'reference' => strtoupper($this->faker->bothify('??##??##??')),
        ];
    }
}
