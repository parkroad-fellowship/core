<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

/**
 * Where the fellowship holds money: one cashbook per account.
 */
enum PRFFinancialAccountType: int implements HasLabel, HasColor, HasIcon
{
    use HasEnumHelpers;

    case PAYBILL = 1;
    case MPESA = 2;
    case BANK = 3;
    case CASH = 4;
    case MSHWARI = 5;
    case PAYSTACK = 6;
    case MONEY_MARKET = 7;

    public function getLabel(): string
    {
        return match ($this) {
            self::PAYBILL => 'M-Pesa Paybill',
            self::MPESA => 'M-Pesa Number',
            self::BANK => 'Bank',
            self::CASH => 'Cash',
            self::MSHWARI => 'M-Shwari',
            self::PAYSTACK => 'Paystack (online giving)',
            self::MONEY_MARKET => 'Money Market / Fixed Deposit',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PAYBILL, self::MPESA, self::MSHWARI => 'success',
            self::BANK, self::MONEY_MARKET => 'info',
            self::CASH => 'warning',
            self::PAYSTACK => 'primary',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::PAYBILL, self::MPESA, self::MSHWARI => 'heroicon-o-device-phone-mobile',
            self::BANK, self::MONEY_MARKET => 'heroicon-o-building-library',
            self::CASH => 'heroicon-o-banknotes',
            self::PAYSTACK => 'heroicon-o-credit-card',
        };
    }
}
