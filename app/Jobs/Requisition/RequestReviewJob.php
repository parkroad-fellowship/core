<?php

namespace App\Jobs\Requisition;

use App\Enums\PRFApprovalStatus;
use App\Models\Member;
use App\Models\Requisition;
use App\Notifications\Requisition\RequestReviewNotification;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Notification;

class RequestReviewJob
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
        $appointedApprover = Member::query()
            ->where([
                'ulid' => $this->data['appointed_approver_ulid'],
            ])
            ->firstOrFail();

        Requisition::query()
            ->where('ulid', $this->ulid)
            ->update([
                'approval_status' => PRFApprovalStatus::UNDER_REVIEW->value,
                'review_requested_at' => now(),
                'appointed_approver_id' => $appointedApprover->id,
            ]);

        $requisition = Requisition::query()
            ->where('ulid', $this->ulid)
            ->firstOrFail();

        Notification::send(
            $appointedApprover,
            new RequestReviewNotification($requisition)
        );
    }
}
