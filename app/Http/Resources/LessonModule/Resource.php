<?php

namespace App\Http\Resources\LessonModule;

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
            'entity' => 'lesson-module',

            'ulid' => $this->ulid,
            'order' => $this->order,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'lesson' => new \App\Http\Resources\Lesson\Resource($this->whenLoaded('lesson')),
            'module' => new \App\Http\Resources\Module\Resource($this->whenLoaded('module')),
            'lesson_member' => new \App\Http\Resources\LessonMember\Resource($this->whenLoaded('lessonMember')),
        ];
    }
}
