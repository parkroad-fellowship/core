<?php

namespace App\Http\Resources\MissionSubscription;

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
            'entity' => 'mission-subscription',

            'ulid' => $this->ulid,
            'status' => $this->status,
            'mission_role' => $this->mission_role,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'mission' => new \App\Http\Resources\Mission\Resource($this->whenLoaded('mission')),
            'member' => new \App\Http\Resources\Member\Resource($this->whenLoaded('member')),
        ];
    }
}
