<?php

namespace App\Http\Resources\EventSpeaker;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Resource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'entity' => 'event-speaker',

            'ulid' => $this->ulid,
            'topic' => $this->topic,
            'description' => $this->description,
            'comments' => $this->comments,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'prf_event' => new \App\Http\Resources\PRFEvent\Resource($this->whenLoaded('prfEvent')),
            'speaker' => new \App\Http\Resources\Speaker\Resource($this->whenLoaded('speaker')),
        ];
    }
}
