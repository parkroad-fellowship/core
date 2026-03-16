<?php

namespace App\Http\Resources\Speaker;

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
            'entity' => 'speaker',

            'ulid' => $this->ulid,
            'name' => $this->name,
            'phone_number' => $this->phone_number,
            'email' => $this->email,
            'title' => $this->title,
            'bio' => $this->bio,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'event_speakers' => JsonResource::collection($this->whenLoaded('eventSpeakers')),
        ];
    }
}
