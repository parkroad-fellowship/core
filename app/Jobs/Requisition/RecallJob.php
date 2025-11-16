<?php

namespace App\Jobs\Requisition;

use App\Enums\PRFApprovalStatus;
use App\Models\Requisition;
use Illuminate\Foundation\Bus\Dispatchable;

class RecallJob
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

        Requisition::query()
            ->where('ulid', $this->ulid)
            ->update([
                'approval_status' => PRFApprovalStatus::RECALLED,
                'approval_notes' => $data['approval_notes'],
                'approved_by' => null,
                'approved_at' => null,
                'rejected_at' => null,
                'review_requested_at' => null,
            ]);
    }
}
