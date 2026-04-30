<?php

namespace App\Http\Resources\AllocationEntry;

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
            'entity' => 'allocation-entry',

            'ulid' => $this->ulid,

            'entry_type' => $this->entry_type,
            'amount' => $this->amount,
            'charge_type' => $this->charge_type,
            'unit_cost' => $this->unit_cost,
            'quantity' => $this->quantity,
            'charge' => $this->charge,
            'narration' => $this->narration,
            'confirmation_message' => $this->confirmation_message,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'accounting_event' => new \App\Http\Resources\AccountingEvent\Resource($this->whenLoaded('accountingEvent')),
            'expense_category' => new \App\Http\Resources\ExpenseCategory\Resource($this->whenLoaded('expenseCategory')),
            'member' => new \App\Http\Resources\Member\Resource($this->whenLoaded('member')),
            'receipts' => \App\Http\Resources\Media\Resource::collection($this->whenLoaded('receipts')),
        ];
    }
}
