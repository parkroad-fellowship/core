<?php

namespace App\Jobs\Requisition;

use App\Enums\PRFApprovalStatus;
use App\Events\Requisition\RequisitionReviewRequested;
use App\Models\Member;
use App\Models\Requisition;
use Illuminate\Foundation\Bus\Dispatchable;

class RequestReviewJob
{
    use Dispatchable;

    public function __construct(
        public string $ulid,
        public array $data,
    ) {}

    public function handle(): void
    {
        $appointedApprover = Member::query()
            ->where([
                'ulid' => $this->data['appointed_approver_ulid'],
            ])
            ->firstOrFail();

        $requisition = Requisition::query()->where('ulid', $this->ulid)->firstOrFail();

        $requisition->update([
            'approval_status' => PRFApprovalStatus::UNDER_REVIEW,
            'review_requested_at' => now(),
            'appointed_approver_id' => $appointedApprover->id,
        ]);

        RequisitionReviewRequested::dispatch($requisition, $appointedApprover);
    }
}
