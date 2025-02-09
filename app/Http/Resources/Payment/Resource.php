<?php

namespace App\Http\Resources\Payment;

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
            'entity' => 'payment',

            'ulid' => $this->ulid,

            'amount' => $this->amount,
            'payment_status' => $this->payment_status,
            'redirect_url' => $this->redirect_url,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'member' => new \App\Http\Resources\Member\Resource($this->whenLoaded('member')),
            'payment_type' => new \App\Http\Resources\PaymentType\Resource($this->whenLoaded('paymentType')),
        ];
    }
}
