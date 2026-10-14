<?php

namespace App\Http\Resources\CourseGroup;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Resource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'entity' => 'course-group',

            'ulid' => $this->ulid,
            'start_date' => $this->start_date,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'group' => new \App\Http\Resources\Group\Resource($this->whenLoaded('group')),
            'course' => new \App\Http\Resources\Course\Resource($this->whenLoaded('course')),
        ];
    }
}
