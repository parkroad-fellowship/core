<?php

namespace App\Jobs\AllocationEntry;

use App\Models\AccountingEvent;
use App\Models\AllocationEntry;
use App\Models\ExpenseCategory;
use App\Models\Member;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Arr;

class UpdateJob
{
    use Dispatchable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $ulid,
        public array $data
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

        $expenseCategory = ExpenseCategory::query()
            ->where('ulid', $data['expense_category_ulid'])
            ->firstOrFail();
        $data['expense_category_id'] = $expenseCategory->id;
        Arr::forget($data, 'expense_category_ulid');

        $member = Member::query()
            ->where('ulid', $data['member_ulid'])
            ->firstOrFail();
        $data['member_id'] = $member->id;
        Arr::forget($data, 'member_ulid');

        $data['amount'] = (intval($data['unit_cost']) * intval($data['quantity'])) + intval($data['charge']);

        $allocationEntry = AllocationEntry::query()
            ->where('ulid', $this->ulid)
            ->firstOrFail();

        $allocationEntry->update($data);

        return $allocationEntry;
    }
}
