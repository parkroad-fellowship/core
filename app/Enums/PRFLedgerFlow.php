<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Direction of a ledger line on its account: money in (receipt) or money out (payment).
 */
enum PRFLedgerFlow: int implements HasLabel, HasColor
{
    use HasEnumHelpers;

    case RECEIPT = 1;
    case PAYMENT = 2;

    public function getLabel(): string
    {
        return match ($this) {
            self::RECEIPT => 'Receipt (money in)',
            self::PAYMENT => 'Payment (money out)',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::RECEIPT => 'success',
            self::PAYMENT => 'danger',
        };
    }

    /**
     * +1 for money in, -1 for money out.
     */
    public function sign(): int
    {
        return $this === self::RECEIPT ? 1 : -1;
    }
}
