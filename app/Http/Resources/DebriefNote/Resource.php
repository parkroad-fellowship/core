<?php

namespace App\Http\Resources\DebriefNote;

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
            'entity' => 'debrief-note',

            'ulid' => $this->ulid,
            'note' => $this->note,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'mission' => new \App\Http\Resources\Mission\Resource($this->whenLoaded('mission')),
        ];
    }
}
