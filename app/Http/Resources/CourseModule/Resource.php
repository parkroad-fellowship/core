<?php

namespace App\Http\Resources\CourseModule;

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
            'entity' => 'course-module',

            'ulid' => $this->ulid,
            'order' => $this->order,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'course' => new \App\Http\Resources\Course\Resource($this->whenLoaded('course')),
            'module' => new \App\Http\Resources\Module\Resource($this->whenLoaded('module')),
            'member_module' => new \App\Http\Resources\MemberModule\Resource($this->whenLoaded('memberModule')),
        ];
    }
}
