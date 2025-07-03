<?php

namespace App\Enums;

enum PRFPromptTime: int
{
    case MORNING = 1;
    case EVENING = 2;
    case AFTERNOON = 3;

    public static function getElements(): array
    {
        return [
            self::MORNING,
            self::EVENING,
            self::AFTERNOON,
        ];
    }

    public static function fromValue(int $value): self
    {
        return match ($value) {
            self::MORNING->value => self::MORNING,
            self::EVENING->value => self::EVENING,
            self::AFTERNOON->value => self::AFTERNOON,
        };
    }

    public static function getOptions(): array
    {
        return [
            self::MORNING->value => 'Morning',
            self::EVENING->value => 'Evening',
            self::AFTERNOON->value => 'Afternoon',
        ];
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::MORNING => 'Morning',
            self::EVENING => 'Evening',
            self::AFTERNOON => 'Afternoon',
        };
    }
}
