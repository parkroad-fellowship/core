<?php

namespace App\Http\Resources\MissionGroundSuggestion;

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
            'entity' => 'mission-ground-suggestion',

            'ulid' => $this->ulid,

            'name' => $this->name,
            'contact_person' => $this->contact_person,
            'contact_number' => $this->contact_number,
            'status' => $this->status,
            'notes' => $this->notes,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'suggestor' => \App\Http\Resources\Member\Resource::make($this->whenLoaded('suggestor')),
        ];
    }
}
