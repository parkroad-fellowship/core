<?php

namespace App\Http\Resources\ReceiptDelivery;

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
            'entity' => 'receipt-delivery',

            'ulid' => $this->ulid,

            'channel' => $this->channel?->value,
            'recipient' => $this->recipient,
            'status' => $this->status?->value,
            'share_url' => $this->share_url,
            'error' => $this->error,
            'sent_at' => $this->sent_at,

            'ledger_entry' => new \App\Http\Resources\LedgerEntry\Resource($this->whenLoaded('ledgerEntry')),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
