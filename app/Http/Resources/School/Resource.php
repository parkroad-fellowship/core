<?php

namespace App\Http\Resources\School;

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
            'entity' => 'school',

            'ulid' => $this->ulid,
            'name' => $this->name,
            'description' => $this->description,
            'total_students' => $this->total_students,
            'address' => $this->address,
            'directions' => $this->directions,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'is_active' => $this->is_active,
            'location' => $this->location,
            'institution_type' => $this->institution_type,
            'distance' => $this->distance,
            'static_duration' => $this->static_duration,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'school_contacts' => \App\Http\Resources\SchoolContact\Resource::collection($this->whenLoaded('schoolContacts')),
        ];
    }
}
