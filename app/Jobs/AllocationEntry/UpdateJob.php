<?php

namespace App\Jobs\AllocationEntry;

use App\Jobs\Concerns\ResolvesULIDs;
use App\Models\AccountingEvent;
use App\Models\AllocationEntry;
use App\Models\ExpenseCategory;
use App\Models\Member;
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

    public function handle(): AllocationEntry
    {
        $allocationEntry = AllocationEntry::query()->where('ulid', $this->ulid)->firstOrFail();

        $attributes = $this->resolveULIDs($this->data, [
            'accounting_event_ulid' => AccountingEvent::class,
            'expense_category_ulid' => ExpenseCategory::class,
            'member_ulid' => Member::class,
        ]);
        $attributes['amount'] =
            ((int) $attributes['unit_cost'] * (int) $attributes['quantity']) + (int) $attributes['charge'];

        $allocationEntry->update($attributes);

        return $allocationEntry;
    }
}
