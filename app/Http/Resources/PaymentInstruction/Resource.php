<?php

namespace App\Http\Resources\PaymentInstruction;

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
            'entity' => 'payment-instruction',

            'ulid' => $this->ulid,

            'payment_method' => $this->payment_method,
            'recipient_name' => $this->recipient_name,
            'reference' => $this->reference,
            'mpesa_phone_number' => $this->mpesa_phone_number,
            'bank_name' => $this->bank_name,
            'bank_account_number' => $this->bank_account_number,
            'bank_account_name' => $this->bank_account_name,
            'bank_branch' => $this->bank_branch,
            'bank_swift_code' => $this->bank_swift_code,
            'paybill_number' => $this->paybill_number,
            'paybill_account_number' => $this->paybill_account_number,
            'till_number' => $this->till_number,
            'amount' => $this->amount,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'requisition' => new \App\Http\Resources\Requisition\Resource($this->whenLoaded('requisition')),
        ];
    }
}
