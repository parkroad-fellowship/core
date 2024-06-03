<?php

namespace App\Enums;

enum PRFMissionStatus: int
{
    case PENDING = 1; // Information is still being gathered
    case APPROVED = 2; // Information has been gathered and can be published for members to subscribe
    case REJECTED = 3; // Information has been gathered but the mission has been rejected
    case CANCELLED = 4; // Mission has been cancelled
    case SERVICED = 5; // Mission has been serviced

    public static function getOptions(): array
    {
        return [
            self::PENDING->value => 'Pending',
            self::APPROVED->value => 'Approved',
            self::REJECTED->value => 'Rejected',
            self::CANCELLED->value => 'Cancelled',
            self::SERVICED->value => 'Serviced',
        ];
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
            self::CANCELLED => 'Cancelled',
            self::SERVICED => 'Serviced',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PENDING => 'yellow',
            self::APPROVED => 'green',
            self::REJECTED => 'red',
            self::CANCELLED => 'red',
            self::SERVICED => 'green',
        };
    }

    public static function fromValue(int $value): self
    {
        return match ($value) {
            self::PENDING->value => self::PENDING,
            self::APPROVED->value => self::APPROVED,
            self::REJECTED->value => self::REJECTED,
            self::CANCELLED->value => self::CANCELLED,
            self::SERVICED->value => self::SERVICED,
        };
    }

    public static function fromEnum(self $enum): self
    {
        return match ($enum) {
            self::PENDING => self::PENDING,
            self::APPROVED => self::APPROVED,
            self::REJECTED => self::REJECTED,
            self::CANCELLED => self::CANCELLED,
            self::SERVICED => self::SERVICED,
        };
    }

    public static function getElements(): array
    {
        return [
            self::PENDING->value,
            self::APPROVED->value,
            self::REJECTED->value,
            self::CANCELLED->value,
            self::SERVICED->value,
        ];
    }
}
