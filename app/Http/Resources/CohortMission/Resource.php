<?php

namespace App\Http\Resources\CohortMission;

use App\Http\Resources\Cohort\Resource as CohortResource;
use App\Http\Resources\Mission\Resource as MissionResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Resource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'entity' => 'cohort_mission',

            'ulid' => $this->ulid,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'cohort' => new CohortResource($this->whenLoaded('cohort')),
            'mission' => new MissionResource($this->whenLoaded('mission')),
        ];
    }
}
