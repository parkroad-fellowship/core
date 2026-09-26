<?php

namespace App\Jobs\Refund;

use App\Enums\PRFFinancialAccountType;
use App\Enums\PRFTransactionType;
use App\Helpers\Utils;
use App\Models\AccountingEvent;
use App\Models\FinancialAccount;
use App\Models\Refund;
use App\Services\Finance\ChartOfAccounts;
use App\Services\Finance\Ledger;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CreateJob
{
    use Dispatchable;

    public function __construct(
        public array $data,
    ) {}

    /**
     * Records money returned after an event and books it in the cashbook (Paybill unless told otherwise).
     */
    public function handle(Ledger $ledger, ChartOfAccounts $chart): Refund
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
        $priorRefunds = Refund::query()->where('accounting_event_id', $accountingEvent->id)->sum('amount');

        $priorCharges = Refund::query()->where('accounting_event_id', $accountingEvent->id)->sum('charge');

        $totalRefunds = $priorRefunds + $refundAmount;
        $totalCharges = $priorCharges + $data['charge'];

        // Org accepts refund_charge (calculated on full balance)
        // Any charges beyond that are the person's responsibility
        $extraCharges = max(0, $totalCharges - $accountingEvent->refund_charge);

        // deficit = what person still owes
        // If total charges exceed org's charge, person must pay the difference
        $data['deficit_amount'] = $accountingEvent->amount_to_refund - $totalRefunds + $extraCharges;

        $accountULID = Arr::pull($data, 'financial_account_ulid');
        $account = is_string($accountULID) && $accountULID !== ''
            ? FinancialAccount::query()->where('ulid', $accountULID)->firstOrFail()
            : $chart->account(PRFFinancialAccountType::PAYBILL);
        $data['financial_account_id'] = $account->id;

        return DB::transaction(function () use ($data, $account, $ledger): Refund {
            $refund = Refund::create($data);

            $ledger->postRefund($refund, $account);

            return $refund;
        });
    }
}
