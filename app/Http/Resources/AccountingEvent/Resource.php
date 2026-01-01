<?php

namespace App\Http\Resources\AccountingEvent;

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
            'entity' => 'accounting-event',

            'ulid' => $this->ulid,

            'name' => $this->name,
            'description' => $this->description,
            'due_date' => $this->due_date,
            'status' => $this->status,
            'responsible_desk' => $this->responsible_desk,
            'spent_amount' => $this->spent_amount,
            'debits' => $this->debits,
            'amount_received' => $this->amount_received,
            'credits' => $this->credits,
            'balance' => $this->balance,
            'refund_charge' => $this->refund_charge,
            'amount_to_refund' => $this->amount_to_refund,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'requisitions' => \App\Http\Resources\Requisition\Resource::collection($this->whenLoaded('requisitions')),
            'refunds' => \App\Http\Resources\Refund\Resource::collection($this->whenLoaded('refunds')),
            'latest_refund' => new \App\Http\Resources\Refund\Resource($this->whenLoaded('latestRefund')),
        ];
    }
}
