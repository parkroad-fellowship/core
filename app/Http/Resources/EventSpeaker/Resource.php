<?php

namespace App\Http\Resources\EventSpeaker;

use App\Http\Resources\PRFEvent\Resource as PRFEventResource;
use App\Http\Resources\Speaker\Resource as SpeakerResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Resource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'entity' => 'event_speaker',

            'ulid' => $this->ulid,
            'topic' => $this->topic,
            'description' => $this->description,
            'comments' => $this->comments,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'prf_event' => new PRFEventResource($this->whenLoaded('prfEvent')),
            'speaker' => new SpeakerResource($this->whenLoaded('speaker')),
        ];
    }
}
