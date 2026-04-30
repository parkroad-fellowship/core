<?php

namespace App\Http\Resources\Role;

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
            'entity' => 'role',

            'name' => $this->name,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'permissions' => \App\Http\Resources\Permission\Resource::collection($this->whenLoaded('permissions')),
        ];
    }
}
