<?php

namespace App\Enums;

enum PRFMissionSubscriptionStatus: int
{
    case PENDING = 1; // Information is still being gathered
    case APPROVED = 2; // Information has been gathered and can be published for members to subscribe
    case WITHDRAWN = 3; // Member has withdrawn from the mission
    case FULLY_SUBSCRIBED = 4; // Mission has enough members to fulfill the mission

    public static function getOptions(): array
    {
        return [
            self::PENDING->value => 'Pending',
            self::APPROVED->value => 'Approved',
            self::WITHDRAWN->value => 'Withdrawn',
            self::FULLY_SUBSCRIBED->value => 'Fully Subscribed',
        ];
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::APPROVED => 'Approved',
            self::WITHDRAWN => 'Withdrawn',
            self::FULLY_SUBSCRIBED => 'Fully Subscribed',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PENDING => 'yellow',
            self::APPROVED => 'green',
            self::WITHDRAWN => 'red',
            self::FULLY_SUBSCRIBED => 'blue',
        };
    }

    public static function fromValue(int $value): self
    {
        return match ($value) {
            self::PENDING->value => self::PENDING,
            self::APPROVED->value => self::APPROVED,
            self::WITHDRAWN->value => self::WITHDRAWN,
            self::FULLY_SUBSCRIBED->value => self::FULLY_SUBSCRIBED,
        };
    }

    public static function fromEnum(self $enum): self
    {
        return match ($enum) {
            self::PENDING => self::PENDING,
            self::APPROVED => self::APPROVED,
            self::WITHDRAWN => self::WITHDRAWN,
            self::FULLY_SUBSCRIBED => self::FULLY_SUBSCRIBED,
        };
    }

    public static function getElements(): array
    {
        return [
            self::PENDING->value,
            self::APPROVED->value,
            self::WITHDRAWN->value,
            self::FULLY_SUBSCRIBED->value,
        ];
    }

    public static function getValues(): array
    {
        return [
            self::PENDING,
            self::APPROVED,
            self::WITHDRAWN,
            self::FULLY_SUBSCRIBED,
        ];
    }
}
