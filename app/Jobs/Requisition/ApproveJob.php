<?php

namespace App\Jobs\Requisition;

use App\Enums\PRFApprovalStatus;
use App\Enums\PRFEntryType;
use App\Enums\PRFResponsibleDesk;
use App\Exports\Requisition\Export;
use App\Helpers\Utils;
use App\Models\AllocationEntry;
use App\Models\Member;
use App\Models\Requisition;
use App\Notifications\Requisition\ApprovalNotification;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Notification;
use Maatwebsite\Excel\Facades\Excel;

class ApproveJob
{
    use Dispatchable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $ulid,
        public array $data
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $data = $this->data;

        $approver = Member::query()
            ->where([
                'ulid' => $data['approved_by_ulid'],
            ])
            ->firstOrFail();

        Requisition::query()
            ->where('ulid', $this->ulid)
            ->update([
                'approval_status' => PRFApprovalStatus::APPROVED,
                'approval_notes' => $data['approval_notes'] ?? null,
                'approved_by' => $approver->id,
                'approved_at' => now(),
            ]);

        $requisition = Requisition::query()
            ->where('ulid', $this->ulid)
            ->firstOrFail();

        // Create an allocation entry reflecting this amount for spending
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

        $notifiables = Member::query()
            ->whereIn('id', collect([
                $requisition->appointed_approver_id,
                $requisition->approved_by,
            ])->unique()->toArray())
            ->orWhereIn('email', collect([
                ...Utils::getDeskEmails(PRFResponsibleDesk::from($requisition->responsible_desk)),
                ...Utils::getDeskEmails(PRFResponsibleDesk::TREASURER_DESK),
            ])->unique()->toArray())
            ->get();

        // Generate an excel sheet
        $fileName = Utils::generateRequisitionFileName(
            requisition: $requisition,
            type: 'approval',
            extension: '.xlsx'
        );
        Excel::store(
            export: new Export(
                requisitionId: $requisition->id,
            ),
            filePath: $fileName,
        );

        Notification::send(
            $notifiables->unique('id'),
            new ApprovalNotification(
                requisition: $requisition,
                fileName: $fileName,
            )
        );
    }
}
