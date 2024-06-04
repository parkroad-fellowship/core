<?php

namespace App\Http\Resources\Mission;

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
            'entity' => 'mission',

            'ulid' => $this->ulid,

            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'capacity' => $this->capacity,
            'mission_prep_notes' => $this->mission_prep_notes,
            'status' => $this->status,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,

            'school_term' => new \App\Http\Resources\SchoolTerm\Resource($this->whenLoaded('schoolTerm')),
            'mission_type' => new \App\Http\Resources\MissionType\Resource($this->whenLoaded('missionType')),
            'school' => new \App\Http\Resources\School\Resource($this->whenLoaded('school')),
        ];
    }
}
