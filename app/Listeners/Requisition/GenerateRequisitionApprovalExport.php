<?php

namespace App\Listeners\Requisition;

use App\Events\Requisition\RequisitionApproved;
use App\Jobs\Requisition\GenerateApprovalExportJob;

/**
 * Builds the approval spreadsheet and sends it (with the approval notification) to the stakeholders.
 */
class GenerateRequisitionApprovalExport
{
    public function handle(RequisitionApproved $event): void
    {
        GenerateApprovalExportJob::dispatch($event->requisition->id);
    }
}
