<?php

namespace App\Enums;

enum PRFAccountEventStatus: int
{
    case PENDING = 1;
    case COMPLETED = 2;
    case CANCELLED = 3;

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
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
