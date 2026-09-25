<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;
use Filament\Support\Contracts\HasLabel;

enum PRFFinancialReportType: int implements HasLabel
{
    use HasEnumHelpers;

    /** Cashbook per account, cash balances, income statement and treasurer report (xlsx). */
    case CASHBOOK = 1;

    /** Per accounting event: disbursed, real expenses, token, refunds and balance (xlsx). */
    case MONTHLY_ACCOUNTABILITY = 2;

    /** Income by designation and by channel, per month (xlsx). */
    case INCOME_DISTRIBUTION = 3;

    /** Ministry highlights and giving for stakeholders and the AGM (pdf). */
    case IMPACT_SUMMARY = 4;

    public function getLabel(): string
    {
        return match ($this) {
            self::CASHBOOK => 'Cashbook & Financial Statements',
            self::MONTHLY_ACCOUNTABILITY => 'Monthly Accountability',
            self::INCOME_DISTRIBUTION => 'Income Distribution',
            self::IMPACT_SUMMARY => 'Impact Summary',
        };
    }

    public function extension(): string
    {
        return $this === self::IMPACT_SUMMARY ? 'pdf' : 'xlsx';
    }

    public function mimeType(): string
    {
        return $this === self::IMPACT_SUMMARY
            ? 'application/pdf'
            : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    }
}
