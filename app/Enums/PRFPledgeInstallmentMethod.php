<?php

namespace App\Enums;

enum PRFPledgeInstallmentMethod: int
{
    case MANUAL = 1;
    case PAYSTACK = 2;

    public static function getOptions(): array
    {
        return [
            self::MANUAL->value => 'Manual',
            self::PAYSTACK->value => 'Paystack',
        ];
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::MANUAL => 'Manual',
            self::PAYSTACK => 'Paystack',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::MANUAL => 'info',
            self::PAYSTACK => 'success',
        };
    }

    public static function getElements(): array
    {
        return [
            self::MANUAL->value,
            self::PAYSTACK->value,
        ];
    }
}
