<?php

namespace App\Jobs\RequisitionItem;

use App\Models\ExpenseCategory;
use App\Models\Requisition;
use App\Models\RequisitionItem;
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
    public function handle(): RequisitionItem
    {
        $data = $this->data;

        $requisition = Requisition::where('ulid', $data['requisition_ulid'])->firstOrFail();
        $data['requisition_id'] = $requisition->id;
        Arr::forget($data, 'requisition_ulid');

        $expenseCategory = ExpenseCategory::where('ulid', $data['expense_category_ulid'])->firstOrFail();
        $data['expense_category_id'] = $expenseCategory->id;
        Arr::forget($data, 'expense_category_ulid');

        $totalPrice = $data['unit_price'] * $data['quantity'];
        $data['total_price'] = $totalPrice;

        return RequisitionItem::create($data);
    }
}
