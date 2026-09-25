<?php

namespace App\Exports\Finance;

use App\Exports\Finance\Sheets\AccountCashbookSheet;
use App\Exports\Finance\Sheets\CashBalancesSheet;
use App\Exports\Finance\Sheets\IncomeDistributionSheet;
use App\Exports\Finance\Sheets\IncomeStatementSheet;
use App\Exports\Finance\Sheets\TreasurerReportSheet;
use App\Services\Finance\FinancialStatements;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * The treasurer's books for a period, laid out like "PRF Financials": a cashbook per account,
 * cash balances, the income statement, income distribution and the treasurer report.
 */
class CashbookExport implements WithMultipleSheets
{
    public function __construct(
        private readonly Carbon $from,
        private readonly Carbon $to,
        private readonly string $organisation,
    ) {}

    /**
     * @return list<object>
     */
    public function sheets(): array
    {
        $statements = app(FinancialStatements::class);
        $balances = $statements->cashBalances($this->from, $this->to);
        $statement = $statements->incomeStatement($this->from, $this->to);
        $previous = $statements->incomeStatement($this->from->copy()->subYear(), $this->to->copy()->subYear());

        return [
            new TreasurerReportSheet($balances, $statement, $this->from, $this->to, $this->organisation),
            ...$balances->map(
                fn(array $balance) => new AccountCashbookSheet(
                    $balance['account'],
                    $this->from,
                    $this->to,
                    $balance['opening'],
                    $this->organisation,
                ),
            )->all(),
            new CashBalancesSheet($balances, $this->from, $this->to, $this->organisation),
            new IncomeStatementSheet($statement, $previous, $this->to, $this->organisation),
            new IncomeDistributionSheet($statements->incomeDistribution($this->from, $this->to), $this->organisation),
        ];
    }
}
