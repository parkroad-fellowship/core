<?php

namespace App\Http\Resources\MissionFaqCategory;

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
            'entity' => 'mission-faq-category',

            'ulid' => $this->ulid,
            'name' => $this->name,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
