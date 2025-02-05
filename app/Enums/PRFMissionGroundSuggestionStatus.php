<?php

namespace App\Enums;

enum PRFMissionGroundSuggestionStatus: int
{
    case NEW = 1;
    case INITIATED_CONTACT = 2;
    case VISIT_SCHEDULED = 3;
    case MISSION_SECURED = 4;

    public static function getElements(): array
    {
        return [
            self::NEW,
            self::INITIATED_CONTACT,
            self::MISSION_SECURED,
            self::VISIT_SCHEDULED,
        ];
    }

    public static function fromValue(int $value): self
    {
        return match ($value) {
            self::NEW->value => self::NEW,
            self::INITIATED_CONTACT->value => self::INITIATED_CONTACT,
            self::MISSION_SECURED->value => self::MISSION_SECURED,
            self::VISIT_SCHEDULED->value => self::VISIT_SCHEDULED,
        };
    }

    public static function getOptions(): array
    {
        return [
            self::NEW->value => 'New',
            self::INITIATED_CONTACT->value => 'Initiated Contact',
            self::MISSION_SECURED->value => 'Mission Secured',
            self::VISIT_SCHEDULED->value => 'Visit Scheduled',
        ];
    }
}
