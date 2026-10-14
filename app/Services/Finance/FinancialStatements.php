<?php

namespace App\Services\Finance;

use App\Enums\PRFLedgerCategoryKind;
use App\Enums\PRFLedgerFlow;
use App\Models\FinancialAccount;
use App\Models\LedgerCategory;
use App\Models\LedgerEntry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * The treasurer's statements, computed from the ledger: cash balances per account, the income
 * statement and the income distribution. Shared by the workbooks, the dashboard and the API.
 */
class FinancialStatements
{
    /**
     * Opening, receipts, payments and closing per account for the period.
     *
     * @return Collection<int, array{account: FinancialAccount, opening: int, receipts: int, payments: int, closing: int}>
     */
    public function cashBalances(Carbon $from, Carbon $to): Collection
    {
        $opening = $this->signedTotalsByAccount(fn($query) => $query->whereDate('transacted_on', '<', $from));

        $movements = LedgerEntry::query()
            ->between($from, $to)
            ->selectRaw('financial_account_id, flow, sum(amount) as total')
            ->groupBy('financial_account_id', 'flow')
            ->get()
            ->groupBy('financial_account_id');

        return FinancialAccount::query()
            ->orderBy('id')
            ->get()
            ->map(function (FinancialAccount $account) use ($opening, $movements): array {
                $lines = $movements->get($account->id, collect());
                $receipts = (int) $lines->firstWhere('flow', PRFLedgerFlow::RECEIPT)?->total;
                $payments = (int) $lines->firstWhere('flow', PRFLedgerFlow::PAYMENT)?->total;
                $openingBalance = (int) ($opening[$account->id] ?? 0);

                return [
                    'account' => $account,
                    'opening' => $openingBalance,
                    'receipts' => $receipts,
                    'payments' => $payments,
                    'closing' => $openingBalance + $receipts - $payments,
                ];
            });
    }

    /**
     * Receipts by income line and expenditure by desk line. Refunds reduce the line they belong to;
     * transfers and opening balances are left out.
     *
     * @return array{receipts: array<string, int>, expenditure: array<string, int>}
     */
    public function incomeStatement(Carbon $from, Carbon $to): array
    {
        $totals = LedgerEntry::query()
            ->between($from, $to)
            ->join('ledger_categories', 'ledger_categories.id', '=', 'ledger_entries.ledger_category_id')
            ->whereIn('ledger_categories.kind', array_map(fn(PRFLedgerCategoryKind $kind) => $kind->value, [
                PRFLedgerCategoryKind::INCOME,
                PRFLedgerCategoryKind::EXPENSE,
                PRFLedgerCategoryKind::CHARGE,
                PRFLedgerCategoryKind::REFUND,
            ]))
            ->selectRaw('ledger_entries.ledger_category_id, ledger_entries.flow, sum(ledger_entries.amount) as total')
            ->groupBy('ledger_entries.ledger_category_id', 'ledger_entries.flow')
            ->get();

        $categories = LedgerCategory::withTrashed()
            ->whereIn('id', $totals->pluck('ledger_category_id'))
            ->get()
            ->keyBy('id');

        $receipts = $this->lines(PRFLedgerCategoryKind::INCOME);
        $expenditure = $this->lines(PRFLedgerCategoryKind::EXPENSE, PRFLedgerCategoryKind::CHARGE);

        foreach ($totals as $total) {
            $category = $categories->get($total->ledger_category_id);

            if ($category === null) {
                continue;
            }

            $signed = (int) $total->total * ($total->flow === PRFLedgerFlow::RECEIPT ? 1 : -1);
            $line = $category->statementLine();

            if ($category->kind === PRFLedgerCategoryKind::INCOME) {
                // Money out of an income line (e.g. a returned gift) reduces that line.
                $receipts[$line] = ($receipts[$line] ?? 0) + $signed;
            } else {
                // Expenses and charges are payments; a refund (receipt) reduces its desk's line.
                $expenditure[$line] = ($expenditure[$line] ?? 0) - $signed;
            }
        }

        return ['receipts' => $receipts, 'expenditure' => $expenditure];
    }

    /**
     * Income per month, by income line and by channel ("means of giving").
     *
     * @return array{months: list<string>, byLine: array<string, array<string, int>>, byChannel: array<string, array<string, int>>}
     */
    public function incomeDistribution(Carbon $from, Carbon $to): array
    {
        $entries = LedgerEntry::query()
            ->between($from, $to)
            ->where('flow', PRFLedgerFlow::RECEIPT)
            ->ofKind(PRFLedgerCategoryKind::INCOME)
            ->with('ledgerCategory')
            ->get(['id', 'ledger_category_id', 'channel', 'amount', 'transacted_on']);

        $months = [];
        for ($month = $from->copy()->startOfMonth(); $month->lte($to); $month->addMonth()) {
            $months[] = $month->format('M Y');
        }

        $byLine = $this->lines(PRFLedgerCategoryKind::INCOME);
        $byLine = array_map(fn() => array_fill_keys($months, 0), $byLine);
        $byChannel = [];

        foreach ($entries as $entry) {
            $month = $entry->transacted_on->format('M Y');
            $line = $entry->ledgerCategory?->statementLine() ?? 'Other Receipts';
            $channel = $entry->channel?->getLabel() ?? 'Unspecified';

            $byLine[$line] ??= array_fill_keys($months, 0);
            $byLine[$line][$month] += $entry->amount;
            $byChannel[$channel] ??= array_fill_keys($months, 0);
            $byChannel[$channel][$month] += $entry->amount;
        }

        return ['months' => $months, 'byLine' => $byLine, 'byChannel' => $byChannel];
    }

    /**
     * Statement lines of the given kinds, in chart order, all starting at zero.
     *
     * @return array<string, int>
     */
    private function lines(PRFLedgerCategoryKind ...$kinds): array
    {
        return LedgerCategory::query()
            ->ofKind(...$kinds)
            ->orderBy('sort')
            ->get()
            ->mapWithKeys(fn(LedgerCategory $category) => [$category->statementLine() => 0])
            ->all();
    }

    /**
     * @param  callable(\Illuminate\Database\Eloquent\Builder<LedgerEntry>): mixed  $constraint
     * @return array<int, int>
     */
    private function signedTotalsByAccount(callable $constraint): array
    {
        $query = LedgerEntry::query()
            ->selectRaw('financial_account_id, ' . FinancialAccount::signedSumSql())
            ->groupBy('financial_account_id');
        $constraint($query);

        return $query
            ->pluck('balance', 'financial_account_id')
            ->map(fn($balance) => (int) $balance)
            ->all();
    }
}
