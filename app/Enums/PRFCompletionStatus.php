<?php

namespace App\Enums;

enum PRFCompletionStatus: int
{
    case INCOMPLETE = 1;
    case COMPLETE = 2;

    public static function getOptions(): array
    {
        return [
            self::INCOMPLETE->value => 'Incomplete',
            self::COMPLETE->value => 'Complete',

        ];
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::INCOMPLETE => 'Incomplete',
            self::COMPLETE => 'Complete',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::INCOMPLETE => 'red',
            self::COMPLETE => 'green',
        };
    }

    public static function fromValue(int $value): self
    {
        return match ($value) {
            self::INCOMPLETE->value => self::INCOMPLETE,
            self::COMPLETE->value => self::COMPLETE,
        };
    }

    public static function fromEnum(self $enum): self
    {
        return match ($enum) {
            self::INCOMPLETE => self::INCOMPLETE,
            self::COMPLETE => self::COMPLETE,
        };
    }

    public static function getElements(): array
    {
        return [
            self::INCOMPLETE->value,
            self::COMPLETE->value,
        ];
    }
}
