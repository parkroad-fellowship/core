<?php

namespace App\Jobs\Requisition;

use App\Jobs\Concerns\ResolvesULIDs;
use App\Models\AccountingEvent;
use App\Models\Requisition;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateJob
{
    use Dispatchable;
    use ResolvesULIDs;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public array $data,
        public string $ulid,
    ) {}

    public function handle(): Requisition
    {
        $requisition = Requisition::query()->where('ulid', $this->ulid)->firstOrFail();

        $attributes = $this->resolveULIDs($this->data, [
            'accounting_event_ulid' => AccountingEvent::class,
        ]);

        $requisition->update($attributes);

        return $requisition;
    }
}
