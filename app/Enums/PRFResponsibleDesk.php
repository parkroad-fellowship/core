<?php

namespace App\Enums;

use Filament\Tables\Filters\SelectFilter;

enum PRFResponsibleDesk: int
{
    case CHAIRPERSON = 1;
    case VICE_CHAIRPERSON_DESK = 2;
    case ORGANISING_SECRETARY_DESK = 3;
    case MISSIONS_DESK = 4;
    case PRAYER_DESK = 5;
    case FOLLOW_UP_DESK = 6;
    case MUSIC_DESK = 7;
    case TREASURER_DESK = 8;

    public static function getOptions(): array
    {
        return [
            self::CHAIRPERSON->value => 'Chairperson\s Desk',
            self::VICE_CHAIRPERSON_DESK->value => 'Vice Chairperson\'s Desk',
            self::ORGANISING_SECRETARY_DESK->value => 'Organising Secretary\'s Desk',
            self::MISSIONS_DESK->value => 'Missions Desk',
            self::PRAYER_DESK->value => 'Prayer Desk',
            self::FOLLOW_UP_DESK->value => 'Follow Up Desk',
            self::MUSIC_DESK->value => 'Music Desk',
            self::TREASURER_DESK->value => 'Treasurer Desk',
        ];
    }

    public static function getFilterOptions(): array
    {
        return [
            self::CHAIRPERSON->value => 'Chairperson',
            self::VICE_CHAIRPERSON_DESK->value => 'Vice Chairperson',
            self::ORGANISING_SECRETARY_DESK->value => 'Organising Secretary',
            self::MISSIONS_DESK->value => 'Missions Desk',
            self::PRAYER_DESK->value => 'Prayer Desk',
            self::FOLLOW_UP_DESK->value => 'Follow Up Desk',
            self::MUSIC_DESK->value => 'Music Desk',
            self::TREASURER_DESK->value => 'Treasurer Desk',
        ];
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::CHAIRPERSON => 'Chairperson',
            self::VICE_CHAIRPERSON_DESK => 'Vice Chairperson',
            self::ORGANISING_SECRETARY_DESK => 'Organising Secretary',
            self::MISSIONS_DESK => 'Missions Desk',
            self::PRAYER_DESK => 'Prayer Desk',
            self::FOLLOW_UP_DESK => 'Follow Up Desk',
            self::MUSIC_DESK => 'Music Desk',
            self::TREASURER_DESK => 'Treasurer Desk',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::CHAIRPERSON => 'primary',
            self::VICE_CHAIRPERSON_DESK => 'success',
            self::ORGANISING_SECRETARY_DESK => 'warning',
            self::MISSIONS_DESK => 'danger',
            self::PRAYER_DESK => 'info',
            self::FOLLOW_UP_DESK => 'primary',
            self::MUSIC_DESK => 'primary',
            self::TREASURER_DESK => 'primary',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::CHAIRPERSON => 'heroicon-o-numbered-list',
            self::VICE_CHAIRPERSON_DESK => 'heroicon-o-numbered-list',
            self::ORGANISING_SECRETARY_DESK => 'heroicon-o-numbered-list',
            self::MISSIONS_DESK => 'heroicon-o-numbered-list',
            self::PRAYER_DESK => 'heroicon-o-numbered-list',
            self::FOLLOW_UP_DESK => 'heroicon-o-numbered-list',
            self::MUSIC_DESK => 'heroicon-o-numbered-list',
            self::TREASURER_DESK => 'heroicon-o-numbered-list',
        };
    }

    public static function getTableFilter(string $column = 'responsible_desk'): SelectFilter
    {
        return SelectFilter::make($column)
            ->label('Requisition Desk')
            ->options(self::getFilterOptions())
            ->placeholder('All desks')
            ->native(false);
    }

    public static function fromValue(int $value): self
    {
        return match ($value) {
            self::CHAIRPERSON->value => self::CHAIRPERSON,
            self::VICE_CHAIRPERSON_DESK->value => self::VICE_CHAIRPERSON_DESK,
            self::ORGANISING_SECRETARY_DESK->value => self::ORGANISING_SECRETARY_DESK,
            self::MISSIONS_DESK->value => self::MISSIONS_DESK,
            self::PRAYER_DESK->value => self::PRAYER_DESK,
            self::FOLLOW_UP_DESK->value => self::FOLLOW_UP_DESK,
            self::MUSIC_DESK->value => self::MUSIC_DESK,
            self::TREASURER_DESK->value => self::TREASURER_DESK,
            default => self::CHAIRPERSON,
        };
    }

    public static function getElements(): array
    {
        return [
            self::CHAIRPERSON->value,
            self::VICE_CHAIRPERSON_DESK->value,
            self::ORGANISING_SECRETARY_DESK->value,
            self::MISSIONS_DESK->value,
            self::PRAYER_DESK->value,
            self::FOLLOW_UP_DESK->value,
            self::MUSIC_DESK->value,
            self::TREASURER_DESK->value,
        ];
    }
}
