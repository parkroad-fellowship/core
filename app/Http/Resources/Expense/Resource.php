<?php

namespace App\Http\Resources\Expense;

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
            'entity' => 'expense',

            'ulid' => $this->ulid,

            'expenseable_type' => match (gettype($this->expenseable_type)) {
                'object' => $this->expenseable_type->value,
                default => (int) $this->expenseable_type,
            },

            'channel_type' => $this->channel_type,
            'charge_type' => $this->charge_type,
            'amount' => $this->amount,
            'charge' => $this->charge,
            'confirmation_message' => $this->confirmation_message,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'expense_category' => new \App\Http\Resources\ExpenseCategory\Resource($this->whenLoaded('expenseCategory')),
            'member' => new \App\Http\Resources\Member\Resource($this->whenLoaded('member')),
        ];
    }
}
