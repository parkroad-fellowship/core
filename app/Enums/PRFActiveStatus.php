<?php

namespace App\Enums;

use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Builder;

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

    public static function getFilterOptions(): array
    {
        return [
            self::ACTIVE->value => '✅ Active',
            self::INACTIVE->value => '❌ Inactive',
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
            self::INACTIVE => 'danger',
            self::ACTIVE => 'success',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::INACTIVE => 'heroicon-o-x-circle',
            self::ACTIVE => 'heroicon-o-check-circle',
        };
    }

    public static function getTableFilter(string $column = 'is_active'): SelectFilter
    {
        return SelectFilter::make($column)
            ->label('📊 Status')
            ->options(self::getFilterOptions())
            ->multiple()
            ->placeholder('🌐 All statuses')
            ->indicator('Status')
            ->default([self::ACTIVE->value])
            ->native(false);
    }

    public static function getTernaryFilter(string $column = 'is_active', string $label = '📊 Active Status'): TernaryFilter
    {
        return TernaryFilter::make($column)
            ->label($label)
            ->placeholder('🌐 All records')
            ->trueLabel('✅ Active only')
            ->falseLabel('❌ Inactive only')
            ->indicator('Status')
            ->queries(
                true: fn (Builder $query) => $query->where($column, self::ACTIVE->value),
                false: fn (Builder $query) => $query->where($column, self::INACTIVE->value),
            );
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
