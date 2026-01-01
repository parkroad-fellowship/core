<?php

namespace App\Jobs\AllocationEntry;

use App\Models\AccountingEvent;
use App\Models\AllocationEntry;
use App\Models\Member;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Arr;

class AddTokenJob
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
    public function handle(): AllocationEntry
    {
        $data = $this->data;

        $accountingEvent = AccountingEvent::query()
            ->where('ulid', $data['accounting_event_ulid'])
            ->firstOrFail();
        $data['accounting_event_id'] = $accountingEvent->id;
        Arr::forget($data, 'accounting_event_ulid');

        $member = Member::query()
            ->where('ulid', $data['member_ulid'])
            ->firstOrFail();
        $data['member_id'] = $member->id;
        Arr::forget($data, 'member_ulid');

        $data['amount'] = intval($data['unit_cost']);
        $data['quantity'] = 1;
        $data['charge'] = 0;

        return AllocationEntry::create($data);
    }
}
