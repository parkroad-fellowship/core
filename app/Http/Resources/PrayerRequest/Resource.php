<?php

namespace App\Http\Resources\PrayerRequest;

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
            'entity' => 'prayer_request',

            'ulid' => $this->ulid,
            'title' => $this->title,
            'description' => $this->description,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // 'member' =>
            // \App\Http\Resources\Member\Resource::collection($this->whenLoaded('member')),

            'member' => new \App\Http\Resources\Member\Resource($this->whenLoaded('member')),
        ];
    }
}
