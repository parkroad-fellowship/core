<?php

namespace App\Enums;

use Filament\Tables\Filters\SelectFilter;

enum PRFSoulDecisionType: int
{
    case SALVATION = 1;
    case REDEDICATION = 2;
    case CAMP = 3;
    case PRAYER = 4;
    case OTHER = 5;

    public static function getOptions(): array
    {
        return [
            self::SALVATION->value => 'Salvation',
            self::REDEDICATION->value => 'Rededication',
            self::CAMP->value => 'Camp',
            self::PRAYER->value => 'Prayer',
            self::OTHER->value => 'Other',
        ];
    }

    public static function getFilterOptions(): array
    {
        return [
            self::SALVATION->value => 'Salvation',
            self::REDEDICATION->value => 'Rededication',
            self::CAMP->value => 'Camp',
            self::PRAYER->value => 'Prayer',
            self::OTHER->value => 'Other',

        ];
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::SALVATION => 'Salvation',
            self::REDEDICATION => 'Rededication',
            self::CAMP => 'Camp',
            self::PRAYER => 'Prayer',
            self::OTHER => 'Other',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::SALVATION => 'success',
            self::REDEDICATION => 'info',
            self::CAMP => 'success',
            self::PRAYER => 'info',
            self::OTHER => 'warning',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::SALVATION => 'heroicon-o-check-circle',
            self::REDEDICATION => 'heroicon-o-refresh',
            self::CAMP => 'heroicon-o-check-circle',
            self::PRAYER => 'heroicon-o-check-circle',
            self::OTHER => 'heroicon-o-question-mark-circle',
        };
    }

    public static function getTableFilter(string $column = 'decision_type'): SelectFilter
    {
        return SelectFilter::make($column)
            ->label('📊 Status')
            ->options(self::getFilterOptions())
            ->multiple()
            ->placeholder('🌐 All statuses')
            ->indicator('Status')
            ->default([self::SALVATION->value, self::REDEDICATION->value, self::OTHER->value])
            ->native(false);
    }

    public static function fromValue(int $value): self
    {
        return match ($value) {
            self::SALVATION->value => self::SALVATION,
            self::REDEDICATION->value => self::REDEDICATION,
            self::CAMP->value => self::CAMP,
            self::PRAYER->value => self::PRAYER,
            self::OTHER->value => self::OTHER,
        };
    }

    public static function fromEnum(self $enum): self
    {
        return match ($enum) {
            self::SALVATION => self::SALVATION,
            self::REDEDICATION => self::REDEDICATION,
            self::CAMP => self::CAMP,
            self::PRAYER => self::PRAYER,
            self::OTHER => self::OTHER,
        };
    }

    public static function getElements(): array
    {
        return [
            self::SALVATION->value => self::SALVATION->getLabel(),
            self::REDEDICATION->value => self::REDEDICATION->getLabel(),
            self::CAMP->value => self::CAMP->getLabel(),
            self::PRAYER->value => self::PRAYER->getLabel(),
            self::OTHER->value => self::OTHER->getLabel(),
        ];
    }

    public static function getValues(): array
    {
        return [
            self::SALVATION->value,
            self::REDEDICATION->value,
            self::CAMP->value,
            self::PRAYER->value,
            self::OTHER->value,
        ];
    }
}
