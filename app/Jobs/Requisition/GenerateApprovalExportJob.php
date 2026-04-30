<?php

namespace App\Jobs\Requisition;

use App\Enums\PRFResponsibleDesk;
use App\Exports\Requisition\Export;
use App\Helpers\Utils;
use App\Models\Member;
use App\Models\Requisition;
use App\Notifications\Requisition\ApprovalNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;
use Maatwebsite\Excel\Facades\Excel;

class GenerateApprovalExportJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $requisitionId
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $requisition = Requisition::query()
            ->findOrFail($this->requisitionId);

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
