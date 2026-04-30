<?php

namespace App\Http\Resources\CourseGroup;

use App\Http\Resources\Course\Resource as CourseResource;
use App\Http\Resources\Group\Resource as GroupResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Resource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'entity' => 'course_group',

            'ulid' => $this->ulid,
            'start_date' => $this->start_date,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'group' => new GroupResource($this->whenLoaded('group')),
            'course' => new CourseResource($this->whenLoaded('course')),
        ];
    }
}
