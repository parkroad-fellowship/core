<?php

namespace App\Http\Resources\Module;

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
            'entity' => 'module',

            'ulid' => $this->ulid,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'is_active' => $this->is_active,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'thumbnail' => new \App\Http\Resources\Media\Resource($this->whenLoaded('thumbnail')),
            'lesson_modules' => \App\Http\Resources\LessonModule\Resource::collection($this->whenLoaded('lessonModules')),
            'member_module' => new \App\Http\Resources\MemberModule\Resource($this->whenLoaded('memberModule')),
        ];
    }
}
