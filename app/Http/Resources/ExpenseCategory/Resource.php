<?php

namespace App\Http\Resources\ExpenseCategory;

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
            'entity' => 'expense-category',

            'ulid' => $this->ulid,

            'name' => $this->name,
            'description' => $this->description,
            'is_active' => $this->is_active,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'expenses' => \App\Http\Resources\AllocationEntry\Resource::collection($this->whenLoaded('expenses')),
        ];
    }
}
