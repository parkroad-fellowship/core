<?php

namespace App\Enums;

enum PRFPromptFrequency: int
{
    case DAILY = 1;
    case WEEKLY = 2;
    case MONTHLY = 3;

    public static function getElements(): array
    {
        return [
            self::DAILY,
            self::WEEKLY,
            self::MONTHLY,
        ];
    }

    public static function fromValue(int $value): self
    {
        return match ($value) {
            self::DAILY->value => self::DAILY,
            self::WEEKLY->value => self::WEEKLY,
            self::MONTHLY->value => self::MONTHLY,
        };
    }

    public static function getOptions(): array
    {
        return [
            self::DAILY->value => 'Daily',
            self::WEEKLY->value => 'Weekly',
            self::MONTHLY->value => 'Monthly',
        ];
    }
}
