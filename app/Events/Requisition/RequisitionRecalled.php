<?php

namespace App\Events\Requisition;

use App\Models\Requisition;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * An approved requisition was recalled and its credit reversed. Carries the approver the recall
 * cleared, so they are still told about it.
 */
class RequisitionRecalled implements ShouldDispatchAfterCommit
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Requisition $requisition,
        public ?int $formerApproverId = null,
    ) {}
}
