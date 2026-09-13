<?php

namespace App\Enums;

enum PRFPledgeStatus: int
{
    case ACTIVE = 1;
    case FULFILLED = 2;
    case PAUSED = 3;
    case ARCHIVED = 4;

    public static function getOptions(): array
    {
        return [
            self::ACTIVE->value => 'Active',
            self::FULFILLED->value => 'Fulfilled',
            self::PAUSED->value => 'Paused',
            self::ARCHIVED->value => 'Archived',
        ];
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::FULFILLED => 'Fulfilled',
            self::PAUSED => 'Paused',
            self::ARCHIVED => 'Archived',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::ACTIVE => 'success',
            self::FULFILLED => 'info',
            self::PAUSED => 'warning',
            self::ARCHIVED => 'gray',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::ACTIVE => 'heroicon-o-bolt',
            self::FULFILLED => 'heroicon-o-check-circle',
            self::PAUSED => 'heroicon-o-pause',
            self::ARCHIVED => 'heroicon-o-archive-box',
        };
    }

    public static function getElements(): array
    {
        return [
            self::ACTIVE->value,
            self::FULFILLED->value,
            self::PAUSED->value,
            self::ARCHIVED->value,
        ];
    }
}
