<?php

namespace App\Jobs\Refund;

use App\Enums\PRFTransactionType;
use App\Helpers\Utils;
use App\Models\AccountingEvent;
use App\Models\Refund;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Arr;

class CreateJob
{
    use Dispatchable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $data,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): Refund
    {
        $data = $this->data;

        $accountingEvent = AccountingEvent::where('ulid', $data['accounting_event_ulid'])->firstOrFail();
        $data['accounting_event_id'] = $accountingEvent->id;
        Arr::forget($data, ['accounting_event_ulid']);

        // Calculate the charge for this refund
        $refundAmount = intval($data['amount']);
        $data['charge'] = Utils::getCharge(
            chargeType: PRFTransactionType::MPESA_PAYBILL_BUSINESS_TARRIFF,
            amount: $refundAmount,
        );

        // Get totals including this new refund
        $priorRefunds = Refund::query()
            ->where('accounting_event_id', $accountingEvent->id)
            ->sum('amount');

        $priorCharges = Refund::query()
            ->where('accounting_event_id', $accountingEvent->id)
            ->sum('charge');

        $totalRefunds = $priorRefunds + $refundAmount;
        $totalCharges = $priorCharges + $data['charge'];

        // Org accepts refund_charge (calculated on full balance)
        // Any charges beyond that are the person's responsibility
        $extraCharges = max(0, $totalCharges - $accountingEvent->refund_charge);

        // deficit = what person still owes
        // If total charges exceed org's charge, person must pay the difference
        $data['deficit_amount'] = $accountingEvent->amount_to_refund - $totalRefunds + $extraCharges;

        return Refund::create($data);
    }
}
