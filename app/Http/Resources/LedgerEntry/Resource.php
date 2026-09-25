<?php

namespace App\Http\Resources\LedgerEntry;

use App\Services\Finance\ReceiptDocument;
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
            'entity' => 'ledger-entry',

            'ulid' => $this->ulid,

            'flow' => $this->flow?->value,
            'channel' => $this->channel?->value,
            'amount' => $this->amount,
            'transacted_on' => $this->transacted_on?->toDateString(),
            'counterparty' => $this->counterparty,
            'description' => $this->description,
            'reference' => $this->reference,
            'receipt_number' => $this->receipt_number,
            'giver_email' => $this->giver_email,
            'giver_phone' => $this->giver_phone,
            'receipt_url' => $this->when(
                $this->receipt_number !== null,
                fn() => app(ReceiptDocument::class)->url($this->resource),
            ),
            'is_auto_posted' => $this->source_key !== null,

            'financial_account' => new \App\Http\Resources\FinancialAccount\Resource($this->whenLoaded(
                'financialAccount',
            )),
            'ledger_category' => new \App\Http\Resources\LedgerCategory\Resource($this->whenLoaded('ledgerCategory')),
            'member' => new \App\Http\Resources\Member\Resource($this->whenLoaded('member')),
            'accounting_event' => new \App\Http\Resources\AccountingEvent\Resource($this->whenLoaded(
                'accountingEvent',
            )),
            'receipt_deliveries' => \App\Http\Resources\ReceiptDelivery\Resource::collection($this->whenLoaded(
                'receiptDeliveries',
            )),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
