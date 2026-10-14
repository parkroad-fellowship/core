<?php

namespace App\Http\Resources\CohortLetter;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Resource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'entity' => 'cohort-letter',

            'ulid' => $this->ulid,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'cohort' => new \App\Http\Resources\Cohort\Resource($this->whenLoaded('cohort')),
            'letter' => new \App\Http\Resources\Letter\Resource($this->whenLoaded('letter')),
        ];
    }
}
