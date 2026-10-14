<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;
use Filament\Support\Contracts\HasLabel;

/**
 * How money moved: the "means of giving" on a receipt.
 */
enum PRFLedgerChannel: int implements HasLabel
{
    use HasEnumHelpers;

    case PAYBILL = 1;
    case MPESA = 2;
    case BANK_TRANSFER = 3;
    case BANK_DEPOSIT = 4;
    case CASH = 5;
    case CHEQUE = 6;
    case PAYSTACK = 7;

    public function getLabel(): string
    {
        return match ($this) {
            self::PAYBILL => 'M-Pesa Paybill',
            self::MPESA => 'M-Pesa Number',
            self::BANK_TRANSFER => 'Bank Transfer',
            self::BANK_DEPOSIT => 'Bank Deposit (cash)',
            self::CASH => 'Cash',
            self::CHEQUE => 'Cheque',
            self::PAYSTACK => 'Paystack (card / online)',
        };
    }

    /**
     * The channel money normally arrives through for an account type.
     */
    public static function defaultFor(PRFFinancialAccountType $type): self
    {
        return match ($type) {
            PRFFinancialAccountType::PAYBILL => self::PAYBILL,
            PRFFinancialAccountType::MPESA, PRFFinancialAccountType::MSHWARI => self::MPESA,
            PRFFinancialAccountType::BANK, PRFFinancialAccountType::MONEY_MARKET => self::BANK_TRANSFER,
            PRFFinancialAccountType::CASH => self::CASH,
            PRFFinancialAccountType::PAYSTACK => self::PAYSTACK,
        };
    }
}
