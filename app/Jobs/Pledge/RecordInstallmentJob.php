<?php

namespace App\Jobs\Pledge;

use App\Enums\PRFLedgerChannel;
use App\Enums\PRFLedgerFlow;
use App\Enums\PRFPledgeInstallmentMethod;
use App\Events\LedgerEntry\IncomeReceipted;
use App\Models\FinancialAccount;
use App\Models\LedgerCategory;
use App\Models\Pledge;
use App\Models\PledgeInstallment;
use App\Models\User;
use App\Services\Finance\ChartOfAccounts;
use App\Services\Finance\Ledger;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RecordInstallmentJob
{
    use Dispatchable;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public array $data,
        public ?User $recordedBy = null,
    ) {}

    /**
     * Record a Treasurer-entered fulfillment for a pledge and advance its due-date cadence.
     * Money received offline is also receipted into the account it landed in.
     */
    public function handle(Ledger $ledger, ChartOfAccounts $chart): PledgeInstallment
    {
        $data = $this->data;

        [$installment, $entry] = DB::transaction(function () use ($data, $ledger, $chart): array {
            $pledge = Pledge::query()->where('ulid', $data['pledge_ulid'])->firstOrFail();
            $fulfilledOn = filled(Arr::get($data, 'fulfilled_on'))
                ? Carbon::parse($data['fulfilled_on'])
                : Carbon::today();
            $account = filled(Arr::get($data, 'financial_account_ulid'))
                ? FinancialAccount::query()->where('ulid', $data['financial_account_ulid'])->firstOrFail()
                : null;
            $method = $this->method($data, $account);

            $installment = PledgeInstallment::create([
                'pledge_id' => $pledge->id,
                'amount' => $data['amount'],
                'fulfilled_on' => $fulfilledOn,
                'method' => $method,
                'notes' => Arr::get($data, 'notes'),
                'recorded_by' => $this->recordedBy?->id,
            ]);

            if ($account === null) {
                return [$installment, null];
            }

            $category = filled(Arr::get($data, 'ledger_category_ulid'))
                ? LedgerCategory::query()->where('ulid', $data['ledger_category_ulid'])->firstOrFail()
                : $chart->category('income.member_contribution');

            $entry = $ledger->post([
                'financial_account_id' => $account->id,
                'ledger_category_id' => $category->id,
                'flow' => PRFLedgerFlow::RECEIPT,
                'channel' =>
                    Arr::get($data, 'channel') ?? $method->toChannel() ?? PRFLedgerChannel::defaultFor($account->type),
                'amount' => (int) $data['amount'],
                'transacted_on' => $fulfilledOn,
                'counterparty' => $pledge->name,
                'description' => Arr::get($data, 'notes') ?? 'Pledge installment',
                'reference' => Arr::get($data, 'reference'),
                'member_id' => $pledge->member_id,
                'giver_email' => Arr::get($data, 'giver_email') ?? $pledge->email,
                'giver_phone' => Arr::get($data, 'giver_phone') ?? $pledge->phone,
                'pledge_installment_id' => $installment->id,
                'source_key' => "pledge_installment:{$installment->id}",
                'recorded_by' => $this->recordedBy?->id,
            ]);

            return [$installment, $entry];
        });

        if ($entry?->receipt_number !== null) {
            IncomeReceipted::dispatch($entry, (bool) Arr::get($data, 'send_receipt', true));
        }

        return $installment;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function method(array $data, ?FinancialAccount $account): PRFPledgeInstallmentMethod
    {
        if (filled(Arr::get($data, 'method'))) {
            return PRFPledgeInstallmentMethod::from((int) $data['method']);
        }

        $channel = Arr::get($data, 'channel');
        $channel = $channel instanceof PRFLedgerChannel ? $channel : PRFLedgerChannel::tryFrom((int) $channel);
        $channel ??= $account ? PRFLedgerChannel::defaultFor($account->type) : null;

        return match ($channel) {
            PRFLedgerChannel::PAYBILL => PRFPledgeInstallmentMethod::PAYBILL,
            PRFLedgerChannel::MPESA => PRFPledgeInstallmentMethod::MPESA,
            PRFLedgerChannel::BANK_TRANSFER, PRFLedgerChannel::BANK_DEPOSIT => PRFPledgeInstallmentMethod::BANK,
            PRFLedgerChannel::CASH => PRFPledgeInstallmentMethod::CASH,
            PRFLedgerChannel::CHEQUE => PRFPledgeInstallmentMethod::CHEQUE,
            PRFLedgerChannel::PAYSTACK => PRFPledgeInstallmentMethod::PAYSTACK,
            null => PRFPledgeInstallmentMethod::MANUAL,
        };
    }
}
