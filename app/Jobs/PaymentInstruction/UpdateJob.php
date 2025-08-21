<?php

namespace App\Jobs\PaymentInstruction;

use App\Models\PaymentInstruction;
use App\Models\Requisition;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Arr;

class UpdateJob
{
    use Dispatchable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $data,
        public string $ulid,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $data = $this->data;

        if (isset($data['requisition_ulid'])) {
            $requisition = Requisition::where('ulid', $data['requisition_ulid'])->firstOrFail();
            $data['requisition_id'] = $requisition->id;
            Arr::forget($data, 'requisition_ulid');
        }

        PaymentInstruction::query()
            ->where('ulid', $this->ulid)
            ->update($data);
    }
}
