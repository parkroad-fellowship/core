<?php

namespace App\Enums;

enum PRFAccountEventStatus: int
{
    case PENDING = 1;
    case COMPLETED = 2;
    case CANCELLED = 3;

    public static function getOptions(): array
    {
        return [
            self::PENDING->value => 'Pending',
            self::COMPLETED->value => 'Completed',
            self::CANCELLED->value => 'Cancelled',
        ];
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::COMPLETED => 'success',
            self::CANCELLED => 'gray',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::PENDING => 'heroicon-o-clock',
            self::COMPLETED => 'heroicon-o-check-circle',
            self::CANCELLED => 'heroicon-o-x-circle',
        };
    }

    public static function fromValue(int $value): self
    {
        return match ($value) {
            self::PENDING->value => self::PENDING,
            self::COMPLETED->value => self::COMPLETED,
            self::CANCELLED->value => self::CANCELLED,
        };
    }
}
