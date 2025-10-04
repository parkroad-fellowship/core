<?php

namespace App\Jobs\Requisition;

use App\Models\AccountingEvent;
use App\Models\Member;
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
    public function handle(): Requisition
    {
        $data = $this->data;

        $accountingEvent = AccountingEvent::where('ulid', $data['accounting_event_ulid'])->firstOrFail();
        $data['accounting_event_id'] = $accountingEvent->id;
        Arr::forget($data, ['accounting_event_ulid']);

        $member = Member::where('ulid', $data['member_ulid'])->firstOrFail();
        $data['member_id'] = $member->id;
        Arr::forget($data, ['member_ulid']);

        return Requisition::create($data);
    }
}
