<?php

namespace App\Http\Resources\Member;

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
            'entity' => 'member',

            'ulid' => $this->ulid,

            'gender' => $this->gender,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'postal_address' => $this->postal_address,
            'phone_number' => $this->phone_number,
            'email' => $this->email,
            'residence' => $this->residence,
            'year_of_salvation' => $this->year_of_salvation,
            'church_volunteer' => $this->church_volunteer,
            'pastor' => $this->pastor,
            'profession_institution' => $this->profession_institution,
            'profession_location' => $this->profession_location,
            'profession_contact' => $this->profession_contact,
            'accept_terms' => $this->accept_terms,
            'approved' => $this->approved,

            'user' => new \App\Http\Resources\User\Resource($this->whenLoaded('user')),
            'marital_status' => new \App\Http\Resources\MaritalStatus\Resource($this->whenLoaded('maritalStatus')),
            'profession' => new \App\Http\Resources\Profession\Resource($this->whenLoaded('profession')),
            'church' => new \App\Http\Resources\Church\Resource($this->whenLoaded('church')),
            'missions' => \App\Http\Resources\MissionSubscription\Resource::collection($this->whenLoaded('missions')),

        ];
    }
}
