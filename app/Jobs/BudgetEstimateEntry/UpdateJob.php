<?php

namespace App\Jobs\BudgetEstimateEntry;

use App\Jobs\Concerns\ResolvesULIDs;
use App\Models\BudgetEstimateEntry;
use App\Models\ExpenseCategory;
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

    public function handle(): BudgetEstimateEntry
    {
        $budgetEstimateEntry = BudgetEstimateEntry::query()->where('ulid', $this->ulid)->firstOrFail();

        $attributes = $this->resolveULIDs($this->data, [
            'expense_category_ulid' => ExpenseCategory::class,
        ]);
        unset($attributes['budget_estimate_ulid']);

        $budgetEstimateEntry->update($attributes);

        return $budgetEstimateEntry;
    }
}
