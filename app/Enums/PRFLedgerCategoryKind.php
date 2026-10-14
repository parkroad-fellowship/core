<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * What a ledger category means for the financial statements.
 *
 * Only INCOME counts as income. REFUND is money returned to a desk and reduces that desk's
 * expense. TRANSFER and OPENING_BALANCE move balances without touching income or expense.
 */
enum PRFLedgerCategoryKind: int implements HasLabel, HasColor
{
    use HasEnumHelpers;

    case INCOME = 1;
    case EXPENSE = 2;
    case REFUND = 3;
    case TRANSFER = 4;
    case CHARGE = 5;
    case OPENING_BALANCE = 6;

    public function getLabel(): string
    {
        return match ($this) {
            self::INCOME => 'Income',
            self::EXPENSE => 'Expense',
            self::REFUND => 'Refund',
            self::TRANSFER => 'Inter-account transfer',
            self::CHARGE => 'Transaction charge',
            self::OPENING_BALANCE => 'Opening balance',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::INCOME => 'success',
            self::EXPENSE => 'danger',
            self::REFUND => 'info',
            self::TRANSFER => 'gray',
            self::CHARGE => 'warning',
            self::OPENING_BALANCE => 'primary',
        };
    }

    /**
     * The flow a line of this kind normally has.
     */
    public function defaultFlow(): ?PRFLedgerFlow
    {
        return match ($this) {
            self::INCOME, self::REFUND, self::OPENING_BALANCE => PRFLedgerFlow::RECEIPT,
            self::EXPENSE, self::CHARGE => PRFLedgerFlow::PAYMENT,
            self::TRANSFER => null,
        };
    }

    /**
     * Whether lines of this kind appear on the income statement.
     */
    public function isOperating(): bool
    {
        return in_array($this, [self::INCOME, self::EXPENSE, self::REFUND, self::CHARGE], true);
    }
}
