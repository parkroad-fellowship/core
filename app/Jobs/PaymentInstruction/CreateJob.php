<?php

namespace App\Jobs\PaymentInstruction;

use App\Models\PaymentInstruction;
use App\Models\Requisition;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Arr;

class CreateJob
{
    use Dispatchable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $data,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): PaymentInstruction
    {
        $data = $this->data;

        $requisition = Requisition::where('ulid', $data['requisition_ulid'])->firstOrFail();
        $data['requisition_id'] = $requisition->id;
        $data['amount'] = $requisition->total_amount;
        Arr::forget($data, 'requisition_ulid');

        return PaymentInstruction::create($data);
    }
}
