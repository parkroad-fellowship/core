<?php

namespace App\Enums;

enum PRFPromptTime: int
{
    case MORNING = 1;
    case EVENING = 2;

    public static function getElements(): array
    {
        return [
            self::MORNING,
            self::EVENING,
        ];
    }

    public static function fromValue(int $value): self
    {
        return match ($value) {
            self::MORNING->value => self::MORNING,
            self::EVENING->value => self::EVENING,
        };
    }

    public static function getOptions(): array
    {
        return [
            self::MORNING->value => 'Morning',
            self::EVENING->value => 'Evening',
        ];
    }
}
