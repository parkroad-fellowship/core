<?php

namespace App\Exports\Finance;

use App\Exports\Finance\Sheets\MonthlyAccountabilitySheet;
use App\Services\Finance\AccountabilityService;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * "Monthly Missions Financials" for every desk: one sheet per month of the period.
 */
class MonthlyAccountabilityExport implements WithMultipleSheets
{
    public function __construct(
        private readonly Carbon $from,
        private readonly Carbon $to,
    ) {}

    /**
     * @return list<object>
     */
    public function sheets(): array
    {
        $service = app(AccountabilityService::class);
        $sheets = [];

        for ($month = $this->from->copy()->startOfMonth(); $month->lte($this->to); $month->addMonth()) {
            $rows = $service->forMonth($month);
            $sheets[] = new MonthlyAccountabilitySheet($month->copy(), $rows, $service->expenseColumns($rows));
        }

        return $sheets;
    }
}
