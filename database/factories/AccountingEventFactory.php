<?php

namespace Database\Factories;

use App\Enums\PRFAccountEventStatus;
use App\Enums\PRFMorphType;
use App\Enums\PRFResponsibleDesk;
use App\Models\AccountingEvent;
use App\Models\Mission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccountingEvent>
 */
class AccountingEventFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // A pending mission: unlike a PRF Event, it doesn't open its own accounting event.
            'accounting_eventable_id' => Mission::factory(),
            'accounting_eventable_type' => PRFMorphType::MISSION,
            'responsible_desk' => PRFResponsibleDesk::MISSIONS_DESK,
            'name' => $this->faker->sentence(3),
            'due_date' => now()->addWeek(),
            'status' => PRFAccountEventStatus::PENDING,
        ];
    }

    public function forDesk(PRFResponsibleDesk $desk): static
    {
        return $this->state(fn() => ['responsible_desk' => $desk]);
    }
}
