<?php

namespace App\Http\Resources\ClassGroup;

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
            'entity' => 'class-group',

            'ulid' => $this->ulid,
            'name' => $this->name,
            'is_active' => $this->is_active,
            'institution_type' => $this->institution_type,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
