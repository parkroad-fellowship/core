<?php

namespace App\Jobs\Refund;

use App\Models\AccountingEvent;
use App\Models\Refund;
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
    public function handle(): Refund
    {
        $data = $this->data;

        $accountingEvent = AccountingEvent::where('ulid', $data['accounting_event_ulid'])->firstOrFail();
        $data['accounting_event_id'] = $accountingEvent->id;
        Arr::forget($data, ['accounting_event_ulid']);

        $priorRefunds = Refund::query()
            ->where('accounting_event_id', $accountingEvent->id)
            ->sum('amount');

        // deficit_amount tracks against amount_to_refund (net amount after PSP charges)
        // not against balance, because the refund_charge is absorbed by the org
        $data['deficit_amount'] = $accountingEvent->amount_to_refund - ($priorRefunds + intval($data['amount']));

        return Refund::create($data);
    }
}
