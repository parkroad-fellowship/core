<?php

namespace App\Enums;

use Filament\Tables\Filters\SelectFilter;

enum PRFMissionStatus: int
{
    case PENDING = 1; // Information is still being gathered
    case APPROVED = 2; // Information has been gathered and can be published for members to subscribe
    case REJECTED = 3; // Information has been gathered but the mission has been rejected
    case FULLY_SUBSCRIBED = 6; // Mission has been fully subscribed
    case CANCELLED = 4; // Mission has been cancelled
    case SERVICED = 5; // Mission has been serviced
    case POSTPONED = 7; // Mission has been postponed

    /**
     * Statuses where a mission is actively accepting or has accepted subscribers.
     */
    public static function subscribable(): array
    {
        return [
            self::APPROVED->value,
            self::SERVICED->value,
            self::FULLY_SUBSCRIBED->value,
        ];
    }

    public static function getOptions(): array
    {
        return [
            self::PENDING->value => 'Pending',
            self::APPROVED->value => 'Approved',
            self::REJECTED->value => 'Rejected',
            self::FULLY_SUBSCRIBED->value => 'Fully Subscribed',
            self::CANCELLED->value => 'Cancelled',
            self::SERVICED->value => 'Serviced',
            self::POSTPONED->value => 'Postponed',
        ];
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
            self::FULLY_SUBSCRIBED => 'Fully Subscribed',
            self::CANCELLED => 'Cancelled',
            self::SERVICED => 'Serviced',
            self::POSTPONED => 'Postponed',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PENDING => 'yellow',
            self::APPROVED => 'green',
            self::REJECTED => 'red',
            self::FULLY_SUBSCRIBED => 'green',
            self::CANCELLED => 'red',
            self::SERVICED => 'green',
            self::POSTPONED => 'yellow',
        };
    }

    public static function fromValue(int $value): self
    {
        return match ($value) {
            self::PENDING->value => self::PENDING,
            self::APPROVED->value => self::APPROVED,
            self::REJECTED->value => self::REJECTED,
            self::FULLY_SUBSCRIBED->value => self::FULLY_SUBSCRIBED,
            self::CANCELLED->value => self::CANCELLED,
            self::SERVICED->value => self::SERVICED,
            self::POSTPONED->value => self::POSTPONED,
        };
    }

    public static function fromEnum(self $enum): self
    {
        return match ($enum) {
            self::PENDING => self::PENDING,
            self::APPROVED => self::APPROVED,
            self::REJECTED => self::REJECTED,
            self::FULLY_SUBSCRIBED => self::FULLY_SUBSCRIBED,
            self::CANCELLED => self::CANCELLED,
            self::SERVICED => self::SERVICED,
            self::POSTPONED => self::POSTPONED,
        };
    }

    public static function getElements(): array
    {
        return [
            self::PENDING->value,
            self::APPROVED->value,
            self::REJECTED->value,
            self::FULLY_SUBSCRIBED->value,
            self::CANCELLED->value,
            self::SERVICED->value,
            self::POSTPONED->value,
        ];
    }

    public static function getValues(): array
    {
        return [
            self::PENDING,
            self::APPROVED,
            self::REJECTED,
            self::FULLY_SUBSCRIBED,
            self::CANCELLED,
            self::SERVICED,
            self::POSTPONED,
        ];
    }

    public static function getFilterOptions(): array
    {
        return [
            self::PENDING->value => '⏳ Pending',
            self::APPROVED->value => '✅ Approved',
            self::REJECTED->value => '❌ Rejected',
            self::FULLY_SUBSCRIBED->value => '👥 Fully Subscribed',
            self::CANCELLED->value => '🚫 Cancelled',
            self::SERVICED->value => '✅ Serviced',
            self::POSTPONED->value => '📅 Postponed',
        ];
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::PENDING => 'heroicon-o-clock',
            self::APPROVED => 'heroicon-o-check-circle',
            self::REJECTED => 'heroicon-o-x-circle',
            self::FULLY_SUBSCRIBED => 'heroicon-o-users',
            self::CANCELLED => 'heroicon-o-no-symbol',
            self::SERVICED => 'heroicon-o-check-badge',
            self::POSTPONED => 'heroicon-o-calendar-days',
        };
    }

    public static function getTableFilter(): SelectFilter
    {
        return SelectFilter::make('mission_status')
            ->label('📊 Mission Status')
            ->options(self::getFilterOptions())
            ->multiple()
            ->placeholder('🌐 All statuses')
            ->indicator('Mission Status')
            ->native(false);
    }
}
