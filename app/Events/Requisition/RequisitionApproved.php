<?php

namespace App\Events\Requisition;

use App\Models\Requisition;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A requisition was approved and its credit entry recorded.
 */
class RequisitionApproved implements ShouldDispatchAfterCommit
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Requisition $requisition,
    ) {}
}
