<?php

namespace App\Jobs\Requisition;

use App\Enums\PRFApprovalStatus;
use App\Enums\PRFEntryType;
use App\Models\AllocationEntry;
use App\Models\Requisition;
use Illuminate\Foundation\Bus\Dispatchable;

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
        $requisition = Requisition::query()
            ->where('ulid', $this->ulid)
            ->firstOrFail();

        // Soft-delete the CREDIT entry that ApproveJob created for this specific requisition
        AllocationEntry::query()
            ->where([
                'accounting_event_id' => $requisition->accounting_event_id,
                'requisition_id' => $requisition->id,
                'entry_type' => PRFEntryType::CREDIT,
            ])
            ->first()
            ?->delete();

        // Model-level update so RequisitionObserver::updated() fires for notifications
        $requisition->update([
            'approval_status' => PRFApprovalStatus::RECALLED,
            'approval_notes' => $this->data['approval_notes'],
            'approved_by' => null,
            'approved_at' => null,
            'rejected_at' => null,
            'review_requested_at' => null,
        ]);
    }
}
