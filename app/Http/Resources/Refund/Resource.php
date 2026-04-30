<?php

namespace App\Http\Resources\Refund;

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
            'entity' => 'refund',

            'ulid' => $this->ulid,

            'amount' => $this->amount,
            'charge' => $this->charge,
            'confirmation_message' => $this->confirmation_message,
            'deficit_amount' => $this->deficit_amount,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'accounting_event' => new \App\Http\Resources\AccountingEvent\Resource($this->whenLoaded('accountingEvent')),
        ];
    }
}
