<?php

namespace App\Enums;

use Filament\Tables\Filters\SelectFilter;

enum PRFMembershipType: int
{
    case FRIEND = 1;
    case YEARLY_MEMBER = 2;
    case LIFETIME_MEMBER = 3;

    public static function getOptions(): array
    {
        return [
            self::FRIEND->value => 'Friend',
            self::YEARLY_MEMBER->value => 'Yearly Member',
            self::LIFETIME_MEMBER->value => 'Lifetime Member',
        ];
    }

    public static function getFilterOptions(): array
    {
        return [
            self::FRIEND->value => '❤️ Friend',
            self::YEARLY_MEMBER->value => '📅 Yearly Member',
            self::LIFETIME_MEMBER->value => '⭐ Lifetime Member',
        ];
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::FRIEND => 'Friend',
            self::YEARLY_MEMBER => 'Yearly Member',
            self::LIFETIME_MEMBER => 'Lifetime Member',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::FRIEND => 'heroicon-o-heart',
            self::YEARLY_MEMBER => 'heroicon-o-calendar',
            self::LIFETIME_MEMBER => 'heroicon-o-star',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::FRIEND => 'gray',
            self::YEARLY_MEMBER => 'warning',
            self::LIFETIME_MEMBER => 'success',
        };
    }

    public function getPrice(): int
    {
        return match ($this) {
            self::FRIEND => 0,
            self::YEARLY_MEMBER => 500,
            self::LIFETIME_MEMBER => 5000,
        };
    }

    public static function getTableFilter(): SelectFilter
    {
        return SelectFilter::make('type')
            ->label('🎫 Membership Type')
            ->options(self::getFilterOptions())
            ->multiple()
            ->placeholder('🌐 All memberships')
            ->indicator('Membership Type')
            ->native(false);
    }

    public static function fromValue(int $value): self
    {
        return match ($value) {
            self::FRIEND->value => self::FRIEND,
            self::YEARLY_MEMBER->value => self::YEARLY_MEMBER,
            self::LIFETIME_MEMBER->value => self::LIFETIME_MEMBER,
        };
    }

    public static function fromEnum(self $enum): self
    {
        return match ($enum) {
            self::FRIEND => self::FRIEND,
            self::YEARLY_MEMBER => self::YEARLY_MEMBER,
            self::LIFETIME_MEMBER => self::LIFETIME_MEMBER,
        };
    }

    public static function getElements(): array
    {
        return [
            self::FRIEND,
            self::YEARLY_MEMBER,
            self::LIFETIME_MEMBER,
        ];
    }
}
