<?php

namespace App\Http\Resources\MissionSession;

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
            'entity' => 'mission-session',

            'ulid' => $this->ulid,

            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'notes' => $this->notes,
            'order' => $this->order,

            'facilitator' => new \App\Http\Resources\Member\Resource($this->whenLoaded('facilitator')),
            'speaker' => new \App\Http\Resources\Member\Resource($this->whenLoaded('speaker')),
            'class_group' => new \App\Http\Resources\ClassGroup\Resource($this->whenLoaded('classGroup')),
            'mission' => new \App\Http\Resources\Mission\Resource($this->whenLoaded('mission')),
            'media' => \App\Http\Resources\Media\Resource::collection($this->whenLoaded('media')),
            'mission_session_transcripts' => \App\Http\Resources\MissionSessionTranscript\Resource::collection($this->whenLoaded('missionSessionTranscripts')),
        ];
    }
}
