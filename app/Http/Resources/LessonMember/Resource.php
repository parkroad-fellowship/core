<?php

namespace App\Http\Resources\LessonMember;

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
            'entity' => 'lesson-member',

            'ulid' => $this->ulid,

            'completion_status' => $this->completion_status,
            'completed_at' => $this->completed_at,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'lesson' => new \App\Http\Resources\Lesson\Resource($this->whenLoaded('lesson')),
            'module' => new \App\Http\Resources\Module\Resource($this->whenLoaded('module')),
            'course' => new \App\Http\Resources\Course\Resource($this->whenLoaded('course')),
            'member' => new \App\Http\Resources\Member\Resource($this->whenLoaded('member')),
        ];
    }
}
