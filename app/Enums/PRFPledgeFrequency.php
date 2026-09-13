<?php

namespace App\Enums;

enum PRFPledgeFrequency: int
{
    case ONE_TIME = 0;
    case MONTHLY = 1;
    case QUARTERLY = 3;
    case YEARLY = 12;

    public static function getOptions(): array
    {
        return [
            self::ONE_TIME->value => 'One-time',
            self::MONTHLY->value => 'Monthly',
            self::QUARTERLY->value => 'Quarterly',
            self::YEARLY->value => 'Yearly',
        ];
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::ONE_TIME => 'One-time',
            self::MONTHLY => 'Monthly',
            self::QUARTERLY => 'Quarterly',
            self::YEARLY => 'Yearly',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::ONE_TIME => 'gray',
            self::MONTHLY => 'info',
            self::QUARTERLY => 'warning',
            self::YEARLY => 'success',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::ONE_TIME => 'heroicon-o-banknotes',
            self::MONTHLY => 'heroicon-o-calendar-days',
            self::QUARTERLY => 'heroicon-o-calendar',
            self::YEARLY => 'heroicon-o-arrow-path',
        };
    }

    public static function getElements(): array
    {
        return [
            self::ONE_TIME->value,
            self::MONTHLY->value,
            self::QUARTERLY->value,
            self::YEARLY->value,
        ];
    }
}
