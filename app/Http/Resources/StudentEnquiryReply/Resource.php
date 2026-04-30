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
            'commentorable_type' => match (gettype($this->commentorable_type)) {
                'object' => $this->commentorable_type->value,
                default => (int) $this->commentorable_type,
            },
            'is_from_chat_bot' => $this->is_from_chat_bot,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'student_enquiry' => new \App\Http\Resources\StudentEnquiry\Resource($this->whenLoaded('studentEnquiry')),
        ];
    }
}
