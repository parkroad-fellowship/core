<?php

namespace App\Jobs\AccountingEvent;

use App\Enums\PRFApprovalStatus;
use App\Enums\PRFEntryType;
use App\Helpers\Utils;
use App\Models\AccountingEvent;
use App\Models\AllocationEntry;
use App\Models\Member;
use App\Models\Requisition;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class MakeZeroRequisitionJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public AccountingEvent $accountingEvent,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $accountingEvent = $this->accountingEvent->fresh();

        $member = Member::query() // Get desk head email
            ->whereEmail(Utils::getDeskEmails($accountingEvent->responsible_desk)[0])
            ->firstOrFail();

        DB::transaction(function () use ($member, $accountingEvent) {
            $requisition = Requisition::create([
                'member_id' => $member->id,
                'accounting_event_id' => $accountingEvent->id,
                'requisition_date' => now(),
                'responsible_desk' => $accountingEvent->responsible_desk,
                'name' => $accountingEvent->name,
                'approval_status' => PRFApprovalStatus::GHOST->value,
                'approval_notes' => 'Auto-generated zero requisition',
                'total_amount' => 0,
            ]);

            // Create an allocation entry reflecting this amount for spending
            AllocationEntry::create([
                'accounting_event_id' => $requisition->accounting_event_id,
                'requisition_id' => $requisition->id,
                'member_id' => $member->id,
                'entry_type' => PRFEntryType::CREDIT,
                'amount' => $requisition->total_amount,
                'unit_cost' => $requisition->total_amount,
                'quantity' => 1,
                'charge' => 0,
                'narration' => 'Credit for 0-based requisition',
            ]);
        });
    }
}
