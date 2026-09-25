<?php

namespace App\Jobs\Requisition;

use App\Enums\PRFApprovalStatus;
use App\Enums\PRFEntryType;
use App\Events\Requisition\RequisitionApproved;
use App\Models\AllocationEntry;
use App\Models\Member;
use App\Models\Requisition;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;

class ApproveJob
{
    use Dispatchable;

    public function __construct(
        public string $ulid,
        public array $data,
        public int $approverUserId,
    ) {}

    public function handle(): Requisition
    {
        return DB::transaction(function (): Requisition {
            $approver = Member::query()->where('user_id', $this->approverUserId)->firstOrFail();
            $requisition = Requisition::query()->where('ulid', $this->ulid)->firstOrFail();

            $requisition->update([
                'approval_status' => PRFApprovalStatus::APPROVED,
                'approval_notes' => $this->data['approval_notes'] ?? null,
                'approved_by' => $approver->id,
                'approved_at' => now(),
            ]);

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

            RequisitionApproved::dispatch($requisition);

            return $requisition;
        });
    }
}
