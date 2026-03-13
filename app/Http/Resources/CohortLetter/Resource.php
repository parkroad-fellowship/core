<?php

namespace App\Http\Resources\CohortLetter;

use App\Http\Resources\Cohort\Resource as CohortResource;
use App\Http\Resources\Letter\Resource as LetterResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Resource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'entity' => 'cohort_letter',

            'ulid' => $this->ulid,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'cohort' => new CohortResource($this->whenLoaded('cohort')),
            'letter' => new LetterResource($this->whenLoaded('letter')),
        ];
    }
}
