<?php

namespace App\Jobs\Requisition;

use App\Enums\PRFApprovalStatus;
use App\Enums\PRFResponsibleDesk;
use App\Helpers\Utils;
use App\Models\Member;
use App\Models\Requisition;
use App\Notifications\Requisition\RejectionNotification;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Notification;

class RejectJob
{
    use Dispatchable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $ulid,
        public array $data,
        public int $rejectorUserId,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $rejector = Member::query()
            ->where('user_id', $this->rejectorUserId)
            ->firstOrFail();

        Requisition::query()
            ->where('ulid', $this->ulid)
            ->update([
                'approval_status' => PRFApprovalStatus::REJECTED,
                'approval_notes' => $this->data['approval_notes'],
                'approved_by' => $rejector->id,
                'rejected_at' => now(),
            ]);

        $requisition = Requisition::query()
            ->where('ulid', $this->ulid)
            ->firstOrFail();

        $notifiables = Member::query()
            ->whereIn('id', collect([
                $requisition->appointed_approver_id,
                $requisition->approved_by,
            ])->unique()->toArray())
            ->orWhereIn('email', collect([
                ...Utils::getDeskEmails(PRFResponsibleDesk::from($requisition->responsible_desk)),
            ])->unique()->toArray())
            ->get();

        Notification::send(
            $notifiables->unique('id'),
            new RejectionNotification($requisition)
        );
    }
}
