<?php

namespace App\Events\Requisition;

use App\Models\Member;
use App\Models\Requisition;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RequisitionReviewRequested implements ShouldDispatchAfterCommit
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Requisition $requisition,
        public Member $appointedApprover,
    ) {}
}
