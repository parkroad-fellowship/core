<?php

namespace App\Jobs\BudgetEstimateEntry;

use App\Models\BudgetEstimate;
use App\Models\BudgetEstimateEntry;
use App\Models\ExpenseCategory;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateJob
{
    use Dispatchable;

    public function __construct(
        public array $data,
    ) {}

    public function handle(): BudgetEstimateEntry
    {
        $budgetEstimate = BudgetEstimate::query()
            ->where('ulid', $this->data['budget_estimate_ulid'])
            ->firstOrFail();

        $expenseCategory = ExpenseCategory::query()
            ->where('ulid', $this->data['expense_category_ulid'])
            ->firstOrFail();

        return BudgetEstimateEntry::create([
            'budget_estimate_id' => $budgetEstimate->id,
            'expense_category_id' => $expenseCategory->id,
            'item_name' => $this->data['item_name'],
            'unit_price' => $this->data['unit_price'],
            'quantity' => $this->data['quantity'],
            'total_price' => $this->data['total_price'],
            'notes' => $this->data['notes'] ?? null,
        ]);
    }
}
