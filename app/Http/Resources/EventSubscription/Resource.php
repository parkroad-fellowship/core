<?php

namespace App\Http\Resources\EventSubscription;

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
            'entity' => 'event-subscription',

            'ulid' => $this->ulid,
            'number_of_attendees' => $this->number_of_attendees,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'prf_event' => new \App\Http\Resources\PRFEvent\Resource($this->whenLoaded('prfEvent')),
            'member' => new \App\Http\Resources\Member\Resource($this->whenLoaded('member')),
        ];
    }
}
