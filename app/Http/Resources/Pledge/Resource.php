<?php

namespace App\Http\Resources\Pledge;

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
            'entity' => 'pledge',

            'ulid' => $this->ulid,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'amount' => $this->amount,
            'frequency' => $this->frequency?->value,
            'frequency_label' => $this->frequency?->getLabel(),
            'annual_amount' => $this->getAnnualAmount(),

            'start_date' => $this->start_date,
            'next_due_on' => $this->next_due_on,
            'last_fulfilled_on' => $this->last_fulfilled_on,
            'status' => $this->status?->value,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'member' => new \App\Http\Resources\Member\Resource($this->whenLoaded('member')),
            'installments' => \App\Http\Resources\PledgeInstallment\Resource::collection($this->whenLoaded(
                'installments',
            )),
        ];
    }

    public function getAnnualAmount(): ?float
    {
        $frequency = $this->frequency;

        if (!$frequency || $frequency->value === 0) {
            return null;
        }

        return $this->amount * (12 / $frequency->value);
    }
}
