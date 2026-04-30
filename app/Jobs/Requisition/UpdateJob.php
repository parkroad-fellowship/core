<?php

namespace App\Jobs\Requisition;

use App\Models\AccountingEvent;
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
        public string $requisitionUlid,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $data = $this->data;

        if (isset($data['accounting_event_ulid'])) {
            $accountingEvent = AccountingEvent::where('ulid', $data['accounting_event_ulid'])->firstOrFail();
            $data['accounting_event_id'] = $accountingEvent->id;
        }
        Arr::forget($data, ['accounting_event_ulid']);

        Requisition::query()
            ->where('ulid', $this->requisitionUlid)
            ->update($data);
    }
}
