<?php

namespace App\Http\Resources\Lesson;

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
            'entity' => 'lesson',

            'ulid' => $this->ulid,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'type' => $this->type,
            'content' => $this->content,
            'video_url' => $this->video_url,
            'audio_url' => $this->audio_url,
            'document_url' => $this->document_url,
            'is_active' => $this->is_active,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'audios' => \App\Http\Resources\Media\Resource::collection($this->whenLoaded('audios')),
            'documents' => \App\Http\Resources\Media\Resource::collection($this->whenLoaded('documents')),
            'videos' => \App\Http\Resources\Media\Resource::collection($this->whenLoaded('videos')),
            'thumbnail' => new \App\Http\Resources\Media\Resource($this->whenLoaded('thumbnail')),
            'lesson_member' => new \App\Http\Resources\LessonMember\Resource($this->whenLoaded('lessonMember')),
        ];
    }
}
