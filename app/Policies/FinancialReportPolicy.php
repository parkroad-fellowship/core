<?php

namespace App\Policies;

use App\Models\FinancialReport;

class FinancialReportPolicy extends BasePolicy
{
    protected string $modelClass = FinancialReport::class;
}
