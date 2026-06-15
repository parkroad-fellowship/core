<?php

namespace App\Http\Resources\Transcript;

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
            'entity' => 'transcript',

            'ulid' => $this->ulid,

            'transcriptable_type' => $this->transcriptable_type,
            'transcriptable' => $this->whenLoaded('transcriptable', function () {
                return [
                    'entity' => str($this->transcriptable->getTable())->replace('_', '-')->value(),
                    'ulid' => $this->transcriptable->ulid,
                ];
            }),

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
