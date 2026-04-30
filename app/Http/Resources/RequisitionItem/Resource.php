<?php

namespace App\Http\Resources\RequisitionItem;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Resource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'entity' => 'requisition-item',

            'ulid' => $this->ulid,

            'item_name' => $this->item_name,
            'narration' => $this->narration,
            'unit_price' => $this->unit_price,
            'quantity' => $this->quantity,
            'total_price' => $this->total_price,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'requisition' => new \App\Http\Resources\Requisition\Resource($this->whenLoaded('requisition')),
            'expense_category' => new \App\Http\Resources\ExpenseCategory\Resource($this->whenLoaded('expenseCategory')),
        ];
    }
}
