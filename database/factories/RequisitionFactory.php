<?php

namespace Database\Factories;

use App\Enums\PRFApprovalStatus;
use App\Enums\PRFResponsibleDesk;
use App\Models\AccountingEvent;
use App\Models\Member;
use App\Models\Requisition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Requisition>
 */
class RequisitionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'member_id' => Member::factory(),
            'accounting_event_id' => AccountingEvent::factory(),
            'requisition_date' => now(),
            'responsible_desk' => PRFResponsibleDesk::MISSIONS_DESK,
            'approval_status' => PRFApprovalStatus::PENDING,
            'total_amount' => $this->faker->numberBetween(1_000, 30_000),
        ];
    }
}
