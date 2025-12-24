<?php

namespace App\Http\Resources\MissionSessionTranscript;

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
            'entity' => 'mission-session-transcript',

            'ulid' => $this->ulid,

            'transcription_status_url' => $this->transcription_status_url,
            'transcription_content_url' => $this->transcription_content_url,
            'status' => $this->status,
            'transcription_content' => $this->transcription_content,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'media' => new \App\Http\Resources\Media\Resource($this->whenLoaded('media')),
        ];
    }
}
