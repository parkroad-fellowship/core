<?php

namespace App\Http\Resources\MissionFaq;

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
            'entity' => 'mission-faq',

            'ulid' => $this->ulid,
            'question' => $this->question,
            'answer' => $this->answer,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'mission_faq_category' => new \App\Http\Resources\MissionFaqCategory\Resource($this->whenLoaded('missionFaqCategory')),
        ];
    }
}
