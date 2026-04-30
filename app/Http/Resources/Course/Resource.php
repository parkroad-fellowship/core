<?php

namespace App\Http\Resources\Course;

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
            'entity' => 'course',

            'ulid' => $this->ulid,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'is_active' => $this->is_active,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'thumbnail' => new \App\Http\Resources\Media\Resource($this->whenLoaded('thumbnail')),
            'course_member' => new \App\Http\Resources\CourseMember\Resource($this->whenLoaded('courseMember')),
            'course_modules' => \App\Http\Resources\CourseModule\Resource::collection($this->whenLoaded('courseModules')),
        ];
    }
}
