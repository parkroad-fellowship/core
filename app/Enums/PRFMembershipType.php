<?php

namespace App\Enums;

enum PRFMembershipType: int
{
    case FRIEND = 1;
    case YEARLY_MEMBER = 2;
    case LIFETIME_MEMBER = 3;

    public static function getElements(): array
    {
        return [
            self::FRIEND,
            self::YEARLY_MEMBER,
            self::LIFETIME_MEMBER,
        ];
    }
}
