<?php

namespace App\Http\Resources\AccountingEvent;

use App\Models\ExpenseCategory;
use App\Services\Finance\AccountabilityService;
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
            'entity' => 'accounting-event',

            'ulid' => $this->ulid,

            'name' => $this->name,
            'description' => $this->description,
            'due_date' => $this->due_date,
            'status' => $this->status,
            'responsible_desk' => $this->responsible_desk,
            'spent_amount' => $this->spent_amount,
            'debits' => $this->debits,
            'amount_received' => $this->amount_received,
            'credits' => $this->credits,
            'balance' => $this->balance,
            'refund_charge' => $this->refund_charge,
            'amount_to_refund' => $this->amount_to_refund,

            'reconciliation_status' => $this->reconciliation_status?->value,
            'reconciliation_remarks' => $this->reconciliation_remarks,
            'reconciled_at' => $this->reconciled_at,
            'accountability' => $this->when(
                $this->resource->relationLoaded('allocationEntries') && $this->resource->relationLoaded('refunds'),
                fn() => $this->accountability(),
            ),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'requisitions' => \App\Http\Resources\Requisition\Resource::collection($this->whenLoaded('requisitions')),
            'refunds' => \App\Http\Resources\Refund\Resource::collection($this->whenLoaded('refunds')),
            'latest_refund' => new \App\Http\Resources\Refund\Resource($this->whenLoaded('latestRefund')),
        ];
    }

    /**
     * The monthly accountability line for this event (see AccountabilityService).
     *
     * @return array<string, mixed>
     */
    private function accountability(): array
    {
        $row = app(AccountabilityService::class)->row($this->resource);
        $categories = ExpenseCategory::query()
            ->whereIn('id', array_keys($row->expenses))
            ->get(['id', 'ulid', 'name'])
            ->keyBy('id');

        return [
            'disbursed' => $row->disbursed,
            'expenses' => collect($row->expenses)->map(fn(int $amount, $categoryId) => [
                'expense_category_ulid' => $categories->get($categoryId)?->ulid,
                'expense_category' => $categories->get($categoryId)?->name ?? 'Uncategorised',
                'amount' => $amount,
            ])->values(),
            'transaction_costs' => $row->transactionCosts,
            'tokens_of_appreciation' => $row->tokens,
            'to_refund' => $row->toRefund(),
            'refunded' => $row->refunded,
            'balance' => $row->balance(),
            'suggested_status' => $row->suggestedStatus()->value,
            'remarks' => $row->remarks(),
        ];
    }
}
