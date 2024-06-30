<?php

namespace App\Http\Resources\MissionQuestion;

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
            'entity' => 'mission-questions',

            'ulid' => $this->ulid,
            'question' => $this->question,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,

            'mission' => new \App\Http\Resources\Mission\Resource($this->whenLoaded('mission')),
        ];
    }
}
