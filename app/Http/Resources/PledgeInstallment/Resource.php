<?php

namespace App\Http\Resources\PledgeInstallment;

use App\Http\Resources\Pledge\Resource as PledgeResource;
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
            'entity' => 'pledge-installment',

            'ulid' => $this->ulid,
            'amount' => $this->amount,
            'fulfilled_on' => $this->fulfilled_on,
            'method' => $this->method?->value,
            'notes' => $this->notes,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'pledge' => new PledgeResource($this->whenLoaded('pledge')),
        ];
    }
}
