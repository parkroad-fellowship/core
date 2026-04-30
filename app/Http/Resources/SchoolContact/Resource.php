<?php

namespace App\Http\Resources\SchoolContact;

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
            'entity' => 'school-contact',

            'ulid' => $this->ulid,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'contact_type' => new \App\Http\Resources\ContactType\Resource($this->whenLoaded('contactType')),
        ];
    }
}
