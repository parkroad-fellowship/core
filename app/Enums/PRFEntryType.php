<?php

namespace App\Enums;

enum PRFEntryType: int
{
    case CREDIT = 1;
    case DEBIT = 2;

    public static function getOptions(): array
    {
        return [
            self::CREDIT->value => 'Credit',
            self::DEBIT->value => 'Debit',
        ];
    }
}
