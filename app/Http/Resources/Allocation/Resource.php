<?php

namespace App\Http\Resources\Allocation;

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
            'entity' => 'allocation',

            'ulid' => $this->ulid,

            'amount' => $this->amount,
            'balance' => $this->balance,
            'total_spend' => $this->total_spend,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'accounting_event' => new \App\Http\Resources\AccountingEvent\Resource($this->whenLoaded('accountingEvent')),
            'allocation_entries' => \App\Http\Resources\AllocationEntry\Resource::collection($this->whenLoaded('allocationEntries')),
        ];
    }
}
