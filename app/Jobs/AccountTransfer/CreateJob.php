<?php

namespace App\Jobs\AccountTransfer;

use App\Enums\PRFLedgerFlow;
use App\Jobs\Concerns\ResolvesULIDs;
use App\Models\AccountTransfer;
use App\Models\FinancialAccount;
use App\Services\Finance\ChartOfAccounts;
use App\Services\Finance\Ledger;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;

/**
 * Moves money between two of the fellowship's accounts: out of one, into the other, plus the
 * charge as a Treasurer's Desk expense. Neither side is income or expense.
 */
class CreateJob
{
    use Dispatchable;
    use ResolvesULIDs;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public array $data,
    ) {}

    public function handle(Ledger $ledger, ChartOfAccounts $chart): AccountTransfer
    {
        return DB::transaction(function () use ($ledger, $chart): AccountTransfer {
            $transfer = AccountTransfer::create($this->resolveULIDs($this->data, [
                'from_financial_account_ulid' => FinancialAccount::class,
                'to_financial_account_ulid' => FinancialAccount::class,
            ]));

            self::postLines($transfer, $ledger, $chart);

            return $transfer;
        });
    }

    public static function postLines(AccountTransfer $transfer, Ledger $ledger, ChartOfAccounts $chart): void
    {
        $shared = [
            'ledger_category_id' => $chart->transfer()->id,
            'amount' => $transfer->amount,
            'transacted_on' => $transfer->transferred_on,
            'reference' => $transfer->reference,
            'account_transfer_id' => $transfer->id,
            'recorded_by' => $transfer->recorded_by,
        ];

        $transfer->loadMissing(['fromAccount', 'toAccount']);

        $ledger->post([
            ...$shared,
            'financial_account_id' => $transfer->from_financial_account_id,
            'flow' => PRFLedgerFlow::PAYMENT,
            'counterparty' => $transfer->toAccount?->name,
            'description' => $transfer->description ?? "Transfer to {$transfer->toAccount?->name}",
            'source_key' => "transfer:{$transfer->id}:out",
        ]);

        $ledger->post([
            ...$shared,
            'financial_account_id' => $transfer->to_financial_account_id,
            'flow' => PRFLedgerFlow::RECEIPT,
            'counterparty' => $transfer->fromAccount?->name,
            'description' => $transfer->description ?? "Transfer from {$transfer->fromAccount?->name}",
            'source_key' => "transfer:{$transfer->id}:in",
        ]);

        if ($transfer->charge > 0) {
            $ledger->post([
                ...$shared,
                'ledger_category_id' => $chart->transactionCosts()->id,
                'financial_account_id' => $transfer->from_financial_account_id,
                'flow' => PRFLedgerFlow::PAYMENT,
                'amount' => $transfer->charge,
                'counterparty' => $transfer->fromAccount?->name,
                'description' => "Charge on transfer to {$transfer->toAccount?->name}",
                'source_key' => "transfer:{$transfer->id}:charge",
            ]);
        }
    }
}
