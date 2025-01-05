<?php

namespace App\Http\Resources\MissionExpense;

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
            'entity' => 'mission-expense',

            'ulid' => $this->ulid,

            'amount_received' => $this->amount_received,
            'amount_spent' => $this->amount_spent,
            'token_amount' => $this->token_amount,
            'amount_to_refund' => $this->amount_to_refund,
            'amount_refunded' => $this->amount_refunded,
            'is_refunded' => $this->is_refunded,
            'balance' => $this->balance,
            'refund_charge' => $this->refund_charge,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'mission' => new \App\Http\Resources\Mission\Resource($this->whenLoaded('mission')),
            'expenses' => \App\Http\Resources\Expense\Resource::collection($this->whenLoaded('expenses')),
        ];
    }
}
