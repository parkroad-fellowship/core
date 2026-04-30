<?php

namespace App\Enums;

use Filament\Tables\Filters\SelectFilter;

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

    public static function getFilterOptions(): array
    {
        return [
            self::MALE->value => '👨 Male',
            self::FEMALE->value => '👩 Female',
        ];
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::MALE => 'Male',
            self::FEMALE => 'Female',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::MALE => 'heroicon-o-user',
            self::FEMALE => 'heroicon-o-user',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::MALE => 'blue',
            self::FEMALE => 'pink',
        };
    }

    public static function getTableFilter(): SelectFilter
    {
        return SelectFilter::make('gender')
            ->label('⚧️ Gender')
            ->options(self::getFilterOptions())
            ->multiple()
            ->placeholder('🌐 All genders')
            ->indicator('Gender')
            ->native(false);
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
