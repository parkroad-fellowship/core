<?php

namespace App\Jobs\Requisition;

use App\Enums\PRFApprovalStatus;
use App\Enums\PRFEntryType;
use App\Models\AllocationEntry;
use App\Models\Member;
use App\Models\Requisition;
use Illuminate\Foundation\Bus\Dispatchable;

class ApproveJob
{
    use Dispatchable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $ulid,
        public array $data,
        public int $approverUserId,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $approver = Member::query()
            ->where('user_id', $this->approverUserId)
            ->firstOrFail();

        $requisition = Requisition::query()
            ->where('ulid', $this->ulid)
            ->firstOrFail();

        // Update to trigger the observer
        $requisition
            ->update([
                'approval_status' => PRFApprovalStatus::APPROVED->value,
                'approval_notes' => $this->data['approval_notes'] ?? null,
                'approved_by' => $approver->id,
                'approved_at' => now(),
            ]);

        $requisition->fresh();

        AllocationEntry::create([
            'accounting_event_id' => $requisition->accounting_event_id,
            'requisition_id' => $requisition->id,
            'member_id' => $approver->id,
            'entry_type' => PRFEntryType::CREDIT,
            'amount' => $requisition->total_amount,
            'unit_cost' => $requisition->total_amount,
            'quantity' => 1,
            'charge' => 0,
            'narration' => 'Credit for approved requisition',
        ]);

        GenerateApprovalExportJob::dispatch($requisition->id);
    }
}
