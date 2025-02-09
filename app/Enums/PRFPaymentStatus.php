<?php

namespace App\Enums;

enum PRFPaymentStatus: int
{
    case PENDING = 1;
    case SUCCESS = 2;
    case CANCELLED = 3;
    case FAILED = 4;

    public static function fromValue(int $value): self
    {
        return match ($value) {
            self::PENDING->value => self::PENDING,
            self::SUCCESS->value => self::SUCCESS,
            self::CANCELLED->value => self::CANCELLED,
            self::FAILED->value => self::FAILED,
            default => throw new \InvalidArgumentException('Invalid payment status value'),
        };
    }

    public static function getOptions(): array
    {
        return [
            self::PENDING->value => 'Pending',
            self::SUCCESS->value => 'Success',
            self::CANCELLED->value => 'Cancelled',
            self::FAILED->value => 'Failed',
        ];
    }
}
