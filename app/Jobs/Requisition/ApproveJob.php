<?php

namespace App\Jobs\Requisition;

use App\Enums\PRFApprovalStatus;
use App\Enums\PRFEntryType;
use App\Events\Requisition\RequisitionApproved;
use App\Models\AllocationEntry;
use App\Models\FinancialAccount;
use App\Models\Member;
use App\Models\Requisition;
use App\Services\Finance\Ledger;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ApproveJob
{
    use Dispatchable;

    public function __construct(
        public string $ulid,
        /** @var array{approval_notes?: string, financial_account_ulid?: string, charge?: int, reference?: string, paid_on?: string} */
        public array $data,
        public int $approverUserId,
    ) {}

    /**
     * Approves the requisition and credits its accounting event. When the treasurer says which
     * account paid it out, the disbursement is also booked in the cashbook.
     */
    public function handle(Ledger $ledger): Requisition
    {
        return DB::transaction(function () use ($ledger): Requisition {
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

            if (filled($this->data['financial_account_ulid'] ?? null)) {
                $ledger->postDisbursement(
                    requisition: $requisition,
                    account: FinancialAccount::query()
                        ->where('ulid', $this->data['financial_account_ulid'])
                        ->firstOrFail(),
                    charge: (int) ($this->data['charge'] ?? 0),
                    reference: $this->data['reference'] ?? null,
                    paidOn: filled($this->data['paid_on'] ?? null) ? Carbon::parse($this->data['paid_on']) : null,
                    recordedBy: $this->approverUserId,
                );
            }

            RequisitionApproved::dispatch($requisition);

            return $requisition;
        });
    }
}
