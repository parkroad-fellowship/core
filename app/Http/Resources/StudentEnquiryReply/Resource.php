<?php

namespace App\Http\Resources\StudentEnquiryReply;

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
            'entity' => 'student-enquiry-reply',

            'ulid' => $this->ulid,
            'content' => $this->content,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,

            'student_enquiry' => new \App\Http\Resources\StudentEnquiry\Resource($this->whenLoaded('studentEnquiry')),
        ];
    }
}
