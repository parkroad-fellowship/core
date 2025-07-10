<?php

namespace App\Http\Resources\Soul;

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
            'entity' => 'soul',

            'ulid' => $this->ulid,
            'full_name' => $this->full_name,
            'admission_number' => $this->admission_number,
            'decision_type' => $this->decision_type,
            'notes' => $this->notes,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'mission' => new \App\Http\Resources\Mission\Resource($this->whenLoaded('mission')),
            'class_group' => new \App\Http\Resources\ClassGroup\Resource($this->whenLoaded('classGroup')),
        ];
    }
}
