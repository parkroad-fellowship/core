<?php

namespace App\Http\Resources\AnnouncementGroup;

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
            'entity' => 'announcement-group',

            'ulid' => $this->ulid,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'announcement' => new \App\Http\Resources\Announcement\Resource($this->whenLoaded('announcement')),
            'group' => new \App\Http\Resources\Group\Resource($this->whenLoaded('group')),
        ];
    }
}
