<?php

namespace App\Enums;

enum PRFPaymentStatus: int
{
    case PENDING = 1;
    case INITIALISED = 2;
    case SUCCESS = 3;
    case CANCELLED = 4;
    case FAILED = 5;

    public static function fromValue(int $value): self
    {
        return match ($value) {
            self::PENDING->value => self::PENDING,
            self::INITIALISED->value => self::INITIALISED,
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
            self::INITIALISED->value => 'Initialised',
            self::SUCCESS->value => 'Success',
            self::CANCELLED->value => 'Cancelled',
            self::FAILED->value => 'Failed',
        ];
    }
}
