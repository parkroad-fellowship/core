<?php

namespace App\Http\Resources\SchoolTerm;

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
            'entity' => 'school_term',

            'ulid' => $this->ulid,
            'name' => $this->name,
            'year' => $this->year,
            'is_active' => $this->is_active,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

        ];
    }
}
