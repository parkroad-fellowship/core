<?php

namespace App\Enums;

enum PRFInstitutionType: int
{
    case HIGH_SCHOOL = 1;
    case PRIMARY_SCHOOL = 2;
    case COLLEGE = 3;
    case UNIVERSITY = 4;
    case COMMUNITY = 5;
    case JUNIOR_SECONDARY_SCHOOL = 6;

    public static function getOptions(): array
    {
        return [
            self::HIGH_SCHOOL->value => 'High School',
            self::PRIMARY_SCHOOL->value => 'Primary School',
            self::COLLEGE->value => 'College',
            self::UNIVERSITY->value => 'University',
            self::COMMUNITY->value => 'Community',
            self::JUNIOR_SECONDARY_SCHOOL->value => 'Junior Secondary School',
        ];
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::HIGH_SCHOOL => 'High School',
            self::PRIMARY_SCHOOL => 'Primary School',
            self::COLLEGE => 'College',
            self::UNIVERSITY => 'University',
            self::COMMUNITY => 'Community',
            self::JUNIOR_SECONDARY_SCHOOL => 'Junior Secondary School'
        };
    }

    public static function getElements(): array
    {
        return [
            self::HIGH_SCHOOL->value,
            self::PRIMARY_SCHOOL->value,
            self::COLLEGE->value,
            self::UNIVERSITY->value,
            self::COMMUNITY->value,
            self::JUNIOR_SECONDARY_SCHOOL->value,
        ];
    }
}
