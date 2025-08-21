<?php

namespace App\Jobs\Requisition;

use App\Enums\PRFApprovalStatus;
use App\Enums\PRFResponsibleDesk;
use App\Helpers\Utils;
use App\Models\Member;
use App\Models\Requisition;
use App\Notifications\Requisition\RejectionNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Bus\Dispatchable;

class RejectJob
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

        $rejector = Member::query()
            ->where([
                'ulid' => $data['approved_by_ulid'],
            ])
            ->firstOrFail();

        Requisition::query()
            ->where('ulid', $this->ulid)
            ->update([
                'approval_status' => PRFApprovalStatus::REJECTED,
                'approval_notes' => $data['approval_notes'],
                'approved_by' => $rejector->id,
                'rejected_at' => now(),
            ]);

        $requisition = Requisition::query()
            ->where('ulid', $this->ulid)
            ->firstOrFail();

        $notifiables = Member::query()
            ->whereIn('id', [
                $requisition->appointed_approver_id,
                $requisition->approved_by,
            ])
            ->whereIn('email', [
                ...Utils::getDeskEmails(PRFResponsibleDesk::from($requisition->responsible_desk)),
            ])
            ->get();

        Notification::send(
            $notifiables,
            new RejectionNotification($requisition)
        );
    }
}
