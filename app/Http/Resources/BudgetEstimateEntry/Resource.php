<?php

namespace App\Http\Resources\BudgetEstimateEntry;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Resource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'entity' => 'budget-estimate-entry',

            'ulid' => $this->ulid,
            'item_name' => $this->item_name,
            'unit_price' => $this->unit_price,
            'quantity' => $this->quantity,
            'total_price' => $this->total_price,
            'cost' => (int) $this->cost,
            'notes' => $this->notes,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'budget_estimate' => new \App\Http\Resources\BudgetEstimate\Resource($this->whenLoaded('budgetEstimate')),
            'expense_category' => new \App\Http\Resources\ExpenseCategory\Resource($this->whenLoaded(
                'expenseCategory',
            )),
        ];
    }
}
