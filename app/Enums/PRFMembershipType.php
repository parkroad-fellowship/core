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

    public static function fromValue(int $value): self
    {
        return match ($value) {
            self::FRIEND->value => self::FRIEND,
            self::YEARLY_MEMBER->value => self::YEARLY_MEMBER,
            self::LIFETIME_MEMBER->value => self::LIFETIME_MEMBER,
        };
    }

    public static function getOptions(): array
    {
        return [
            self::FRIEND->value => 'Friend',
            self::YEARLY_MEMBER->value => 'Yearly Member',
            self::LIFETIME_MEMBER->value => 'Lifetime Member',
        ];
    }
}
