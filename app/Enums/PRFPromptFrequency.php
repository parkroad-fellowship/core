<?php

namespace App\Enums;

enum PRFPromptFrequency: int
{
    case DAILY = 1;
    case WEEKLY = 2;
    case MONTHLY = 3;
    case ONCE = 4;

    public static function getElements(): array
    {
        return [
            self::DAILY,
            self::WEEKLY,
            self::MONTHLY,
            self::ONCE,
        ];
    }

    public static function fromValue(int $value): self
    {
        return match ($value) {
            self::DAILY->value => self::DAILY,
            self::WEEKLY->value => self::WEEKLY,
            self::MONTHLY->value => self::MONTHLY,
            self::ONCE->value => self::ONCE,
        };
    }

    public static function getOptions(): array
    {
        return [
            self::DAILY->value => 'Daily',
            self::WEEKLY->value => 'Weekly',
            self::MONTHLY->value => 'Monthly',
            self::ONCE->value => 'Once',
        ];
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::DAILY => 'Daily',
            self::WEEKLY => 'Weekly',
            self::MONTHLY => 'Monthly',
            self::ONCE => 'Once',
        };
    }
}
