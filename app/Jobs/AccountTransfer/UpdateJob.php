<?php

namespace App\Jobs\AccountTransfer;

use App\Jobs\Concerns\ResolvesULIDs;
use App\Models\AccountTransfer;
use App\Models\FinancialAccount;
use App\Models\LedgerEntry;
use App\Services\Finance\ChartOfAccounts;
use App\Services\Finance\Ledger;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;

class UpdateJob
{
    use Dispatchable;
    use ResolvesULIDs;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public array $data,
        public string $ulid,
    ) {}

    /**
     * Re-posts the transfer's cashbook lines so they always match the transfer.
     */
    public function handle(Ledger $ledger, ChartOfAccounts $chart): AccountTransfer
    {
        return DB::transaction(function () use ($ledger, $chart): AccountTransfer {
            $transfer = AccountTransfer::query()->where('ulid', $this->ulid)->firstOrFail();

            $transfer->update($this->resolveULIDs($this->data, [
                'from_financial_account_ulid' => FinancialAccount::class,
                'to_financial_account_ulid' => FinancialAccount::class,
            ]));

            $transfer
                ->ledgerEntries()
                ->get()
                ->each(fn(LedgerEntry $entry) => $entry->forceDelete());

            CreateJob::postLines($transfer->fresh() ?? $transfer, $ledger, $chart);

            return $transfer;
        });
    }
}
