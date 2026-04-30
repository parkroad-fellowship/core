<?php

namespace App\Http\Resources\MissionOfflineMember;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Resource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'entity' => 'mission-offline-member',
            'ulid' => $this->ulid,
            'name' => $this->name,
            'phone' => $this->phone,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'mission' => new \App\Http\Resources\Mission\Resource($this->whenLoaded('mission')),
        ];
    }
}
