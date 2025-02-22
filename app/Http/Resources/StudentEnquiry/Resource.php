<?php

namespace App\Http\Resources\StudentEnquiry;

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
            'entity' => 'student-enquiry',

            'ulid' => $this->ulid,
            'content' => $this->content,
            'has_replies' => $this->has_replies,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'student' => new \App\Http\Resources\Mission\Resource($this->whenLoaded('student')),
            'mission_faq' => new \App\Http\Resources\ClassGroup\Resource($this->whenLoaded('missionFaq')),
        ];
    }
}
