<?php

namespace App\Enums;

enum PRFSMSStatus: int
{
    case QUEUED = 1;
    case SENT = 2;
    case DELIVERED = 3;
    case FAILED = 4;
    case UNKNOWN = 5;

    public static function getOptions(): array
    {
        return collect(self::cases())->mapWithKeys(fn(self $case) => [$case->value => $case->getLabel()])->all();
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::QUEUED => 'Queued',
            self::SENT => 'Sent',
            self::DELIVERED => 'Delivered',
            self::FAILED => 'Failed',
            self::UNKNOWN => 'Unknown',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::QUEUED => 'gray',
            self::SENT => 'info',
            self::DELIVERED => 'success',
            self::FAILED => 'danger',
            self::UNKNOWN => 'warning',
        };
    }
}
