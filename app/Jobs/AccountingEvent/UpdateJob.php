<?php

namespace App\Jobs\AccountingEvent;

use App\Enums\PRFMorphType;
use App\Enums\PRFReconciliationStatus;
use App\Models\AccountingEvent;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateJob
{
    use Dispatchable;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public array $data,
        public string $ulid,
    ) {}

    public function handle(): AccountingEvent
    {
        $accountingEvent = AccountingEvent::query()->where('ulid', $this->ulid)->firstOrFail();

        $attributes = $this->data;

        if (array_key_exists('accounting_eventable_ulid', $attributes)) {
            $attributes['accounting_eventable_id'] = PRFMorphType::fromValue(
                $attributes['accounting_eventable_type'],
            )->getModel()::query()
                ->where('ulid', $attributes['accounting_eventable_ulid'])
                ->firstOrFail()
                ->getKey();
            unset($attributes['accounting_eventable_ulid']);
        }

        if (array_key_exists('reconciliation_status', $attributes)) {
            $attributes['reconciled_at'] = (int) $attributes['reconciliation_status']
            === PRFReconciliationStatus::PENDING->value
                ? null
                : now();
        } else {
            unset($attributes['reconciled_by']);
        }

        $accountingEvent->update($attributes);

        return $accountingEvent;
    }
}
