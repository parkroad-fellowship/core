<?php

namespace App\Http\Resources\Student;

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
            'entity' => 'student',

            'ulid' => $this->ulid,
            'name' => $this->name,
            'email' => $this->email,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
