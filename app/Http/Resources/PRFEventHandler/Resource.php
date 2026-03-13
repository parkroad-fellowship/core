<?php

namespace App\Http\Resources\PRFEventHandler;

use App\Http\Resources\Member\Resource as MemberResource;
use App\Http\Resources\PRFEvent\Resource as PRFEventResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Resource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'entity' => 'prf_event_handler',

            'ulid' => $this->ulid,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'prf_event' => new PRFEventResource($this->whenLoaded('prfEvent')),
            'member' => new MemberResource($this->whenLoaded('member')),
        ];
    }
}
