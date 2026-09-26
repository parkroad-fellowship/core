<?php

namespace Database\Factories;

use App\Models\AccountingEvent;
use App\Models\Refund;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Refund>
 */
class RefundFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'accounting_event_id' => AccountingEvent::factory(),
            'amount' => 500,
            'charge' => 0,
            'deficit_amount' => 0,
            'confirmation_message' => strtoupper($this->faker->bothify('??##??##?? Confirmed.')),
        ];
    }
}
