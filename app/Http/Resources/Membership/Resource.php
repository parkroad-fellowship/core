<?php

namespace App\Http\Resources\Membership;

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
            'entity' => 'membership',

            'ulid' => $this->ulid,
            'type' => $this->type,
            'approved' => $this->approved,
            'amount' => $this->amount,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'spiritual_year' => new \App\Http\Resources\SpiritualYear\Resource($this->whenLoaded('spiritualYear')),
            'member' => new \App\Http\Resources\Member\Resource($this->whenLoaded('member')),
        ];
    }
}
