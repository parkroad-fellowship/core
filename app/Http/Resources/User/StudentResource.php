<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class StudentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'entity' => 'user',

            'ulid' => $this->ulid,
            'name' => $this->name,
            'email' => $this->email,
            'timezone' => $this->timezone,

            'password' => Arr::get($this->additional, 'password'),
            'token' => Arr::get($this->additional, 'token'),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'roles' => \App\Http\Resources\Role\Resource::collection($this->whenLoaded('roles')),
            'student' => new \App\Http\Resources\Student\Resource($this->whenLoaded('student')),
        ];
    }
}
