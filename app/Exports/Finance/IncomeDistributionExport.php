<?php

namespace App\Exports\Finance;

use App\Exports\Finance\Sheets\IncomeDistributionSheet;
use App\Services\Finance\FinancialStatements;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class IncomeDistributionExport implements WithMultipleSheets
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
        return [new IncomeDistributionSheet(
            app(FinancialStatements::class)->incomeDistribution($this->from, $this->to),
            $this->organisation,
        )];
    }
}
