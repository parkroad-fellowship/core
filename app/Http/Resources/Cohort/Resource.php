<?php

namespace App\Http\Resources\Cohort;

use App\Http\Resources\CohortLetter\Resource as CohortLetterResource;
use App\Http\Resources\CohortMission\Resource as CohortMissionResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Resource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'entity' => 'cohort',

            'ulid' => $this->ulid,
            'title' => $this->title,
            'slug' => $this->slug,
            'start_date' => $this->start_date,
            'is_active' => $this->is_active,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'cohort_missions' => CohortMissionResource::collection($this->whenLoaded('cohortMissions')),
            'cohort_letters' => CohortLetterResource::collection($this->whenLoaded('cohortLetters')),
        ];
    }
}
