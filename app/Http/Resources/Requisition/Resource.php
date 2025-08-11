<?php

namespace App\Http\Resources\Requisition;

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
            'entity' => 'requisition',

            'ulid' => $this->ulid,

            'requisition_date' => $this->requisition_date,
            'responsible_desk' => $this->responsible_desk,
            'approval_status' => $this->approval_status,
            'approval_notes' => $this->approval_notes,
            'remarks' => $this->remarks,
            'total_amount' => $this->total_amount,
            'verified_at' => $this->verified_at,
            'approved_at' => $this->approved_at,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'member' => new \App\Http\Resources\Member\Resource($this->whenLoaded('member')),
            'verified_by' => new \App\Http\Resources\Member\Resource($this->whenLoaded('verifiedBy')),
            'appointed_approver' => new \App\Http\Resources\Member\Resource($this->whenLoaded('appointedApprover')),
            'approved_by' => new \App\Http\Resources\Member\Resource($this->whenLoaded('approvedBy')),
            'accounting_event' => new \App\Http\Resources\AccountingEvent\Resource($this->whenLoaded('accountingEvent')),
            'requisition_items' => \App\Http\Resources\RequisitionItem\Resource::collection($this->whenLoaded('requisitionItems')),
            'payment_instructions' => \App\Http\Resources\PaymentInstruction\Resource::collection($this->whenLoaded('paymentInstructions')),

        ];
    }
}
