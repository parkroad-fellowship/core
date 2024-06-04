<?php

namespace App\Enums;

enum PRFGender: int
{
    case MALE = 1;
    case FEMALE = 2;

    public static function getOptions(): array
    {
        return [
            self::MALE->value => 'Male',
            self::FEMALE->value => 'Female',

        ];
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::MALE => 'Male',
            self::FEMALE => 'Female',
        };
    }

    public static function fromValue(int $value): self
    {
        return match ($value) {
            self::MALE->value => self::MALE,
            self::FEMALE->value => self::FEMALE,
        };
    }

    public static function fromEnum(self $enum): self
    {
        return match ($enum) {
            self::MALE => self::MALE,
            self::FEMALE => self::FEMALE,
        };
    }

    public static function getElements(): array
    {
        return [
            self::MALE->value,
            self::FEMALE->value,
        ];
    }
}
