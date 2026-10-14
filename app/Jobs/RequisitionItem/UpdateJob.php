<?php

namespace App\Jobs\RequisitionItem;

use App\Jobs\Concerns\ResolvesULIDs;
use App\Models\ExpenseCategory;
use App\Models\Requisition;
use App\Models\RequisitionItem;
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

    public function handle(): RequisitionItem
    {
        $requisitionItem = RequisitionItem::query()->where('ulid', $this->ulid)->firstOrFail();

        $attributes = $this->resolveULIDs($this->data, [
            'requisition_ulid' => Requisition::class,
            'expense_category_ulid' => ExpenseCategory::class,
        ]);
        $attributes['total_price'] = $attributes['unit_price'] * $attributes['quantity'];

        $requisitionItem->update($attributes);

        return $requisitionItem;
    }
}
