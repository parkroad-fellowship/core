<?php

namespace App\Enums;

enum PRFInstitutionType: int
{
    case HIGH_SCHOOL = 1;
    case PRIMARY_SCHOOL = 2;
    case COLLEGE = 3;
    case UNIVERSITY = 4;

    public static function getOptions(): array
    {
        return [
            self::HIGH_SCHOOL->value => 'High School',
            self::PRIMARY_SCHOOL->value => 'Primary School',
            self::COLLEGE->value => 'College',
            self::UNIVERSITY->value => 'University',

        ];
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::HIGH_SCHOOL => 'High School',
            self::PRIMARY_SCHOOL => 'Primary School',
            self::COLLEGE => 'College',
            self::UNIVERSITY => 'University',

        };
    }

    public static function getElements(): array
    {
        return [
            self::HIGH_SCHOOL->value,
            self::PRIMARY_SCHOOL->value,
            self::COLLEGE->value,
            self::UNIVERSITY->value,
        ];
    }
}
