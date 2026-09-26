<?php

namespace App\Jobs\Requisition;

use App\Exports\Requisition\Export;
use App\Helpers\Utils;
use App\Models\Requisition;
use App\Notifications\Requisition\RequisitionApprovedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Support\Facades\Notification;
use Maatwebsite\Excel\Facades\Excel;

#[Queue('long')]
#[Tries(3)]
class GenerateApprovalExportJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $requisitionId,
    ) {}

    public function handle(): void
    {
        $requisition = Requisition::query()->findOrFail($this->requisitionId);

        $notifiables = $requisition->stakeholders(includeTreasury: true);

        // Generate an excel sheet
        $fileName = Utils::generateRequisitionFileName(requisition: $requisition, type: 'approval', extension: '.xlsx');
        Excel::store(export: new Export(requisitionId: $requisition->id), filePath: $fileName);

        Notification::send(
            $notifiables,
            new RequisitionApprovedNotification(requisition: $requisition, fileName: $fileName),
        );
    }
}
