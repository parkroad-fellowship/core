<?php

namespace App\Http\Resources\FinancialAccount;

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
            'entity' => 'financial-account',

            'ulid' => $this->ulid,

            'name' => $this->name,
            'type' => $this->type?->value,
            'identifier' => $this->identifier,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'balance' => $this->balance,

            'ledger_entries' => \App\Http\Resources\LedgerEntry\Resource::collection($this->whenLoaded(
                'ledgerEntries',
            )),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
