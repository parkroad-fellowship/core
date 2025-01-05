<?php

namespace App\Enums;

enum PRFChannelType: int
{
    case M_PESA = 1;

    public static function getOptions(): array
    {
        return [
            self::M_PESA->value => 'M-Pesa',
        ];
    }

    public function getElements(): array
    {
        return [
            self::M_PESA->value,
        ];
    }

    public static function fromValue(int $value): self
    {
        return match ($value) {
            self::M_PESA->value => self::M_PESA,
        };
    }
}
