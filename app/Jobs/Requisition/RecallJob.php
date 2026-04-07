<?php

namespace App\Jobs\Requisition;

use App\Enums\PRFApprovalStatus;
use App\Enums\PRFEntryType;
use App\Models\AllocationEntry;
use App\Models\Member;
use App\Models\Requisition;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;

class RecallJob
{
    use Dispatchable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $ulid,
        public array $data,
        public int $actorUserId,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        DB::transaction(function () {
            $requisition = Requisition::query()
                ->where('ulid', $this->ulid)
                ->firstOrFail();

            $actor = Member::query()
                ->where('user_id', $this->actorUserId)
                ->firstOrFail();

            // Create a reversing DEBIT entry to cancel out the original CREDIT,
            // preserving the full audit trail instead of deleting history.
            $creditEntry = AllocationEntry::query()
                ->where([
                    'accounting_event_id' => $requisition->accounting_event_id,
                    'requisition_id' => $requisition->id,
                    'entry_type' => PRFEntryType::CREDIT,
                ])
                ->first();

            if ($creditEntry) {
                AllocationEntry::create([
                    'accounting_event_id' => $requisition->accounting_event_id,
                    'requisition_id' => $requisition->id,
                    'member_id' => $actor->id,
                    'entry_type' => PRFEntryType::DEBIT,
                    'amount' => $creditEntry->amount,
                    'unit_cost' => $creditEntry->unit_cost,
                    'quantity' => $creditEntry->quantity,
                    'charge' => $creditEntry->charge,
                    'narration' => 'Debit reversal for recalled requisition',
                ]);
            }

            // Model-level update so RequisitionObserver::updated() fires for notifications
            $requisition->update([
                'approval_status' => PRFApprovalStatus::RECALLED->value,
                'approval_notes' => $this->data['approval_notes'],
                'approved_by' => null,
                'approved_at' => null,
                'rejected_at' => null,
                'review_requested_at' => null,
            ]);
        });
    }
}
