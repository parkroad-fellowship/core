<?php

namespace App\Http\Resources\Group;

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
            'entity' => 'group',

            'ulid' => $this->ulid,
            'name' => $this->name,
            'description' => $this->description,
            'official_whatsapp_link' => $this->official_whatsapp_link,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

        ];
    }
}
