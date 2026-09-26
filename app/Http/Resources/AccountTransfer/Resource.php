<?php

namespace App\Http\Resources\AccountTransfer;

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
            'entity' => 'account-transfer',

            'ulid' => $this->ulid,

            'amount' => $this->amount,
            'charge' => $this->charge,
            'transferred_on' => $this->transferred_on?->toDateString(),
            'reference' => $this->reference,
            'description' => $this->description,

            'from_account' => new \App\Http\Resources\FinancialAccount\Resource($this->whenLoaded('fromAccount')),
            'to_account' => new \App\Http\Resources\FinancialAccount\Resource($this->whenLoaded('toAccount')),
            'ledger_entries' => \App\Http\Resources\LedgerEntry\Resource::collection($this->whenLoaded(
                'ledgerEntries',
            )),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
