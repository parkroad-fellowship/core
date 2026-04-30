<?php

namespace App\Http\Resources\ChatBot;

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
            'entity' => 'chat_bot',

            'ulid' => $this->ulid,
            'name' => $this->name,
            'description' => $this->description,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
