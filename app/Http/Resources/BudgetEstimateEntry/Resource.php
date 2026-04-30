<?php

namespace App\Http\Resources\BudgetEstimateEntry;

use App\Http\Resources\BudgetEstimate\Resource as BudgetEstimateResource;
use App\Http\Resources\ExpenseCategory\Resource as ExpenseCategoryResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Resource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'entity' => 'budget_estimate_entry',

            'ulid' => $this->ulid,
            'item_name' => $this->item_name,
            'unit_price' => $this->unit_price,
            'quantity' => $this->quantity,
            'total_price' => $this->total_price,
            'notes' => $this->notes,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'budget_estimate' => new BudgetEstimateResource($this->whenLoaded('budgetEstimate')),
            'expense_category' => new ExpenseCategoryResource($this->whenLoaded('expenseCategory')),
        ];
    }
}
