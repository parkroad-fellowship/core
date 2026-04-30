<?php

namespace App\Http\Resources\BudgetEstimate;

use App\Http\Resources\BudgetEstimateEntry\Resource as BudgetEstimateEntryResource;
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
            'is_active' => $this->is_active,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'budget_estimate_entries' => BudgetEstimateEntryResource::collection($this->whenLoaded('budgetEstimateEntries')),
        ];
    }
}
