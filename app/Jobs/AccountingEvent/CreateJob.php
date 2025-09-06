<?php

namespace App\Jobs\AccountingEvent;

use App\Enums\PRFMorphType;
use App\Models\AccountingEvent;
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
    public function handle(): AccountingEvent
    {
        $data = $this->data;

        $accountingEventable = PRFMorphType::fromValue($data['accounting_eventable_type'])->getModel()::query()
            ->where('ulid', $data['accounting_eventable_ulid'])
            ->first();
        $data['accounting_eventable_id'] = $accountingEventable->id;
        Arr::forget($data, ['accounting_eventable_ulid']);

        return AccountingEvent::create($data);
    }
}
