<?php

namespace Database\Factories;

use App\Enums\PRFDeliveryStatus;
use App\Enums\PRFReceiptChannel;
use App\Models\LedgerEntry;
use App\Models\ReceiptDelivery;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReceiptDelivery>
 */
class ReceiptDeliveryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ledger_entry_id' => LedgerEntry::factory(),
            'channel' => PRFReceiptChannel::EMAIL,
            'recipient' => $this->faker->safeEmail(),
            'status' => PRFDeliveryStatus::SENT,
            'sent_at' => now(),
        ];
    }
}
