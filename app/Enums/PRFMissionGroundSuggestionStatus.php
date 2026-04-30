<?php

namespace App\Enums;

enum PRFMissionGroundSuggestionStatus: int
{
    case PENDING = 1;
    case INITIATED_CONTACT = 2;
    case VISIT_SCHEDULED = 3;
    case MISSION_SECURED = 4;
    case COMPLETED = 5;
    case IGNORE = 6;

    public static function getElements(): array
    {
        return [
            self::PENDING,
            self::INITIATED_CONTACT,
            self::MISSION_SECURED,
            self::VISIT_SCHEDULED,
            self::COMPLETED,
            self::IGNORE,
        ];
    }

    public static function fromValue(int $value): self
    {
        return match ($value) {
            self::PENDING->value => self::PENDING,
            self::INITIATED_CONTACT->value => self::INITIATED_CONTACT,
            self::MISSION_SECURED->value => self::MISSION_SECURED,
            self::VISIT_SCHEDULED->value => self::VISIT_SCHEDULED,
            self::COMPLETED->value => self::COMPLETED,
            self::IGNORE->value => self::IGNORE,
        };
    }

    public static function getOptions(): array
    {
        return [
            self::PENDING->value => 'Pending',
            self::INITIATED_CONTACT->value => 'Initiated Contact',
            self::VISIT_SCHEDULED->value => 'Visit Scheduled',
            self::MISSION_SECURED->value => 'Mission Secured',
            self::COMPLETED->value => 'Completed',
            self::IGNORE->value => 'Ignore',
        ];
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
