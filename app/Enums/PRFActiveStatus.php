<?php

namespace App\Enums;

enum PRFActiveStatus: int
{
    case INACTIVE = 1;
    case ACTIVE = 2;

    public static function getOptions(): array
    {
        return [
            self::ACTIVE->value => 'Active',
            self::INACTIVE->value => 'Inactive',

        ];
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::INACTIVE => 'Inactive',
            self::ACTIVE => 'Active',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::INACTIVE => 'red',
            self::ACTIVE => 'green',
        };
    }

    public static function fromValue(int $value): self
    {
        return match ($value) {
            self::INACTIVE->value => self::INACTIVE,
            self::ACTIVE->value => self::ACTIVE,
        };
    }

    public static function fromEnum(self $enum): self
    {
        return match ($enum) {
            self::INACTIVE => self::INACTIVE,
            self::ACTIVE => self::ACTIVE,
        };
    }

    public static function getElements(): array
    {
        return [
            self::INACTIVE->value,
            self::ACTIVE->value,
        ];
    }
}
