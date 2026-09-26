<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PRFPledgeInstallmentMethod: int implements HasLabel, HasColor
{
    use HasEnumHelpers;

    /** Legacy: recorded by hand before channels were tracked. */
    case MANUAL = 1;
    case PAYSTACK = 2;
    case PAYBILL = 3;
    case MPESA = 4;
    case BANK = 5;
    case CASH = 6;
    case CHEQUE = 7;

    public function getLabel(): string
    {
        return match ($this) {
            self::MANUAL => 'Manual',
            self::PAYSTACK => 'Paystack',
            self::PAYBILL => 'M-Pesa Paybill',
            self::MPESA => 'M-Pesa Number',
            self::BANK => 'Bank',
            self::CASH => 'Cash',
            self::CHEQUE => 'Cheque',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PAYSTACK => 'success',
            default => 'info',
        };
    }

    /**
     * Methods the treasurer records by hand; each needs the account the money landed in.
     *
     * @return list<self>
     */
    public static function offline(): array
    {
        return [self::PAYBILL, self::MPESA, self::BANK, self::CASH, self::CHEQUE];
    }

    public function toChannel(): ?PRFLedgerChannel
    {
        return match ($this) {
            self::PAYBILL => PRFLedgerChannel::PAYBILL,
            self::MPESA => PRFLedgerChannel::MPESA,
            self::BANK => PRFLedgerChannel::BANK_TRANSFER,
            self::CASH => PRFLedgerChannel::CASH,
            self::CHEQUE => PRFLedgerChannel::CHEQUE,
            self::PAYSTACK => PRFLedgerChannel::PAYSTACK,
            self::MANUAL => null,
        };
    }
}
