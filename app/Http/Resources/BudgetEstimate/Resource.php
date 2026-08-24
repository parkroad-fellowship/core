<?php

namespace App\Http\Resources\BudgetEstimate;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Resource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'entity' => 'budget_estimate',

            'ulid' => $this->ulid,
            'grand_total' => $this->grand_total,
            'baseline_people' => $this->baseline_people,
            'is_active' => $this->is_active,

            'mission_type' => new \App\Http\Resources\MissionType\Resource($this->whenLoaded('missionType')),
            'mission_type_ulid' => $this->whenLoaded('missionType', fn() => $this->missionType?->ulid),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'budget_estimate_entries' => \App\Http\Resources\BudgetEstimateEntry\Resource::collection($this->whenLoaded(
                'budgetEstimateEntries',
            )),
        ];
    }
}
