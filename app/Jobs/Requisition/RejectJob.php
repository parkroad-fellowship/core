<?php

namespace App\Jobs\Requisition;

use App\Enums\PRFApprovalStatus;
use App\Events\Requisition\RequisitionRejected;
use App\Models\Member;
use App\Models\Requisition;
use Illuminate\Foundation\Bus\Dispatchable;

class RejectJob
{
    use Dispatchable;

    public function __construct(
        public string $ulid,
        public array $data,
        public int $rejectorUserId,
    ) {}

    public function handle(): Requisition
    {
        $rejector = Member::query()->where('user_id', $this->rejectorUserId)->firstOrFail();
        $requisition = Requisition::query()->where('ulid', $this->ulid)->firstOrFail();

        $requisition->update([
            'approval_status' => PRFApprovalStatus::REJECTED,
            'approval_notes' => $this->data['approval_notes'],
            'approved_by' => $rejector->id,
            'rejected_at' => now(),
        ]);

        RequisitionRejected::dispatch($requisition);

        return $requisition;
    }
}
