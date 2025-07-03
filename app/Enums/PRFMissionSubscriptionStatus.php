<?php

namespace App\Enums;

use Filament\Tables\Filters\SelectFilter;

enum PRFMissionSubscriptionStatus: int
{
    case PENDING = 1; // Information is still being gathered
    case APPROVED = 2; // Information has been gathered and can be published for members to subscribe
    case WITHDRAWN = 3; // Member has withdrawn from the mission
    case FULLY_SUBSCRIBED = 4; // Mission has enough members to fulfill the mission
    case CONFLICT = 5; // Mission has a conflict with another mission

    public static function getOptions(): array
    {
        return [
            self::PENDING->value => 'Pending',
            self::APPROVED->value => 'Approved',
            self::WITHDRAWN->value => 'Withdrawn',
            self::FULLY_SUBSCRIBED->value => 'Fully Subscribed',
            self::CONFLICT->value => 'Conflict',
        ];
    }

    public static function getFilterOptions(): array
    {
        return [
            self::PENDING->value => '⏳ Pending Approval',
            self::APPROVED->value => '✅ Approved & Active',
            self::WITHDRAWN->value => '❌ Withdrawn',
            self::FULLY_SUBSCRIBED->value => '👥 Fully Subscribed',
            self::CONFLICT->value => '⚠️ Schedule Conflict',
        ];
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::APPROVED => 'Approved',
            self::WITHDRAWN => 'Withdrawn',
            self::FULLY_SUBSCRIBED => 'Fully Subscribed',
            self::CONFLICT => 'Conflict',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::APPROVED => 'success',
            self::WITHDRAWN => 'danger',
            self::FULLY_SUBSCRIBED => 'info',
            self::CONFLICT => 'danger',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::PENDING => 'heroicon-o-clock',
            self::APPROVED => 'heroicon-o-check-circle',
            self::WITHDRAWN => 'heroicon-o-x-circle',
            self::FULLY_SUBSCRIBED => 'heroicon-o-users',
            self::CONFLICT => 'heroicon-o-exclamation-triangle',
        };
    }

    public static function getTableFilter(string $column = 'status'): SelectFilter
    {
        return SelectFilter::make($column)
            ->label('📊 Subscription Status')
            ->options(self::getFilterOptions())
            ->multiple()
            ->placeholder('🌐 All statuses')
            ->indicator('Status')
            ->default([self::APPROVED->value]) // Default to approved
            ->native(false);
    }

    public static function fromValue(int $value): self
    {
        return match ($value) {
            self::PENDING->value => self::PENDING,
            self::APPROVED->value => self::APPROVED,
            self::WITHDRAWN->value => self::WITHDRAWN,
            self::FULLY_SUBSCRIBED->value => self::FULLY_SUBSCRIBED,
            self::CONFLICT->value => self::CONFLICT,
        };
    }

    public static function fromEnum(self $enum): self
    {
        return match ($enum) {
            self::PENDING => self::PENDING,
            self::APPROVED => self::APPROVED,
            self::WITHDRAWN => self::WITHDRAWN,
            self::FULLY_SUBSCRIBED => self::FULLY_SUBSCRIBED,
            self::CONFLICT => self::CONFLICT,
        };
    }

    public static function getElements(): array
    {
        return [
            self::PENDING->value,
            self::APPROVED->value,
            self::WITHDRAWN->value,
            self::FULLY_SUBSCRIBED->value,
            self::CONFLICT->value,
        ];
    }

    public static function getValues(): array
    {
        return [
            self::PENDING,
            self::APPROVED,
            self::WITHDRAWN,
            self::FULLY_SUBSCRIBED,
            self::CONFLICT,
        ];
    }
}
