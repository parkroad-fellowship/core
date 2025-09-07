<?php

namespace App\Http\Resources\PRFEventParticipant;

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
            'entity' => 'prf-event-participant',

            'ulid' => $this->ulid,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'member' => new \App\Http\Resources\Member\Resource($this->whenLoaded('member')),
            'accounting_event' => new \App\Http\Resources\AccountingEvent\Resource($this->whenLoaded('accountingEvent')),
        ];
    }
}
