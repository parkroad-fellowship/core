<?php

namespace App\Enums;

enum PRFMissionRole: int
{
    case MEMBER = 1;
    case LEADER = 2;
    case ASSISTANT_LEADER = 3;

    public static function getOptions(): array
    {
        return [
            self::MEMBER->value => 'Member',
            self::LEADER->value => 'Mission Leader',
            self::ASSISTANT_LEADER->value => 'Assistant Leader',

        ];
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::MEMBER => 'Member',
            self::LEADER => 'Mission Leader',
            self::ASSISTANT_LEADER => 'Assistant Leader',
        };
    }

    public static function fromValue(int $value): self
    {
        return match ($value) {
            self::MEMBER->value => self::MEMBER,
            self::LEADER->value => self::LEADER,
            self::ASSISTANT_LEADER->value => self::ASSISTANT_LEADER,
        };
    }

    public static function fromEnum(self $enum): self
    {
        return match ($enum) {
            self::MEMBER => self::MEMBER,
            self::LEADER => self::LEADER,
            self::ASSISTANT_LEADER => self::ASSISTANT_LEADER,
        };
    }

    public static function getElements(): array
    {
        return [
            self::MEMBER->value,
            self::LEADER->value,
            self::ASSISTANT_LEADER->value,
        ];
    }
}
