<?php

namespace App\Jobs\BudgetEstimateEntry;

use App\Models\BudgetEstimateEntry;
use App\Models\ExpenseCategory;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateJob
{
    use Dispatchable;

    public function __construct(
        public array $data,
        public string $ulid,
    ) {}

    public function handle(): void
    {
        $update = $this->data;

        if (isset($update['expense_category_ulid'])) {
            $expenseCategory = ExpenseCategory::query()
                ->where('ulid', $update['expense_category_ulid'])
                ->firstOrFail();
            $update['expense_category_id'] = $expenseCategory->id;
            unset($update['expense_category_ulid']);
        }

        unset($update['budget_estimate_ulid']);

        BudgetEstimateEntry::query()
            ->where('ulid', $this->ulid)
            ->firstOrFail()
            ->update($update);
    }
}
