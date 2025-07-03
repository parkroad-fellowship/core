<?php

namespace App\Enums;

use Filament\Tables\Filters\SelectFilter;

enum PRFMissionRole: int
{
    case MEMBER = 1;
    case LEADER = 2;
    case ASSISTANT_LEADER = 3;
    case DISCIPLESHIP_TRAINER = 4;
    case MUSIC_INSTRUMENTS = 5;
    case TRANSPORTATION = 6;

    public static function getOptions(): array
    {
        return [
            self::MEMBER->value => 'Member',
            self::LEADER->value => 'Mission Leader',
            self::ASSISTANT_LEADER->value => 'Assistant Leader',
            self::DISCIPLESHIP_TRAINER->value => 'Discipleship Trainer',
            self::MUSIC_INSTRUMENTS->value => 'Music Instruments',
            self::TRANSPORTATION->value => 'Transportation',
        ];
    }

    public static function getFilterOptions(): array
    {
        return [
            self::MEMBER->value => '👥 Member',
            self::LEADER->value => '⭐ Mission Leader',
            self::ASSISTANT_LEADER->value => '🔄 Assistant Leader',
            self::DISCIPLESHIP_TRAINER->value => '📚 Discipleship Trainer',
            self::MUSIC_INSTRUMENTS->value => '🎵 Music Instruments',
            self::TRANSPORTATION->value => '🚗 Transportation',
        ];
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::MEMBER => 'Member',
            self::LEADER => 'Mission Leader',
            self::ASSISTANT_LEADER => 'Assistant Leader',
            self::DISCIPLESHIP_TRAINER => 'Discipleship Trainer',
            self::MUSIC_INSTRUMENTS => 'Music Instruments',
            self::TRANSPORTATION => 'Transportation',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::MEMBER => 'heroicon-o-user',
            self::LEADER => 'heroicon-o-star',
            self::ASSISTANT_LEADER => 'heroicon-o-user-plus',
            self::DISCIPLESHIP_TRAINER => 'heroicon-o-academic-cap',
            self::MUSIC_INSTRUMENTS => 'heroicon-o-musical-note',
            self::TRANSPORTATION => 'heroicon-o-truck',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::MEMBER => 'gray',
            self::LEADER => 'danger',
            self::ASSISTANT_LEADER => 'warning',
            self::DISCIPLESHIP_TRAINER => 'success',
            self::MUSIC_INSTRUMENTS => 'info',
            self::TRANSPORTATION => 'primary',
        };
    }

    public static function getTableFilter(): SelectFilter
    {
        return SelectFilter::make('mission_role')
            ->label('🎭 Mission Role')
            ->options(self::getFilterOptions())
            ->multiple()
            ->placeholder('🌐 All roles')
            ->indicator('Mission Role')
            ->native(false);
    }

    public static function fromValue(int $value): self
    {
        return match ($value) {
            self::MEMBER->value => self::MEMBER,
            self::LEADER->value => self::LEADER,
            self::ASSISTANT_LEADER->value => self::ASSISTANT_LEADER,
            self::DISCIPLESHIP_TRAINER->value => self::DISCIPLESHIP_TRAINER,
            self::MUSIC_INSTRUMENTS->value => self::MUSIC_INSTRUMENTS,
            self::TRANSPORTATION->value => self::TRANSPORTATION,
        };
    }

    public static function fromEnum(self $enum): self
    {
        return match ($enum) {
            self::MEMBER => self::MEMBER,
            self::LEADER => self::LEADER,
            self::ASSISTANT_LEADER => self::ASSISTANT_LEADER,
            self::DISCIPLESHIP_TRAINER => self::DISCIPLESHIP_TRAINER,
            self::MUSIC_INSTRUMENTS => self::MUSIC_INSTRUMENTS,
            self::TRANSPORTATION => self::TRANSPORTATION,
        };
    }

    public static function getElements(): array
    {
        return [
            self::MEMBER->value,
            self::LEADER->value,
            self::ASSISTANT_LEADER->value,
            self::DISCIPLESHIP_TRAINER->value,
            self::MUSIC_INSTRUMENTS->value,
            self::TRANSPORTATION->value,
        ];
    }
}
