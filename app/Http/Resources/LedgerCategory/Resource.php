<?php

namespace App\Http\Resources\LedgerCategory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Resource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'entity' => 'ledger-category',

            'ulid' => $this->ulid,

            'name' => $this->name,
            'code' => $this->code,
            'kind' => $this->kind?->value,
            'responsible_desk' => $this->responsible_desk?->value,
            'statement_line' => $this->statement_line,
            'sort' => $this->sort,
            'is_active' => $this->is_active,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
