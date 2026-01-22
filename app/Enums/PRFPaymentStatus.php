<?php

namespace App\Enums;

use Filament\Tables\Filters\SelectFilter;
use InvalidArgumentException;

enum PRFPaymentStatus: int
{
    case PENDING = 1;
    case INITIALISED = 2;
    case SUCCESS = 3;
    case CANCELLED = 4;
    case FAILED = 5;

    public static function getOptions(): array
    {
        return [
            self::PENDING->value => 'Pending',
            self::INITIALISED->value => 'Initialised',
            self::SUCCESS->value => 'Success',
            self::CANCELLED->value => 'Cancelled',
            self::FAILED->value => 'Failed',
        ];
    }

    public static function getFilterOptions(): array
    {
        return [
            self::PENDING->value => '⏳ Pending',
            self::INITIALISED->value => '🔄 Initialised',
            self::SUCCESS->value => '✅ Success',
            self::CANCELLED->value => '🚫 Cancelled',
            self::FAILED->value => '❌ Failed',
        ];
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::INITIALISED => 'Initialised',
            self::SUCCESS => 'Success',
            self::CANCELLED => 'Cancelled',
            self::FAILED => 'Failed',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::PENDING => 'heroicon-o-clock',
            self::INITIALISED => 'heroicon-o-arrow-path',
            self::SUCCESS => 'heroicon-o-check-circle',
            self::CANCELLED => 'heroicon-o-x-circle',
            self::FAILED => 'heroicon-o-exclamation-triangle',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::INITIALISED => 'info',
            self::SUCCESS => 'success',
            self::CANCELLED => 'gray',
            self::FAILED => 'danger',
        };
    }

    public static function getTableFilter(): SelectFilter
    {
        return SelectFilter::make('payment_status')
            ->label('💳 Payment Status')
            ->options(self::getFilterOptions())
            ->multiple()
            ->placeholder('🌐 All payments')
            ->indicator('Payment Status')
            ->native(false);
    }

    public static function fromValue(int $value): self
    {
        return match ($value) {
            self::PENDING->value => self::PENDING,
            self::INITIALISED->value => self::INITIALISED,
            self::SUCCESS->value => self::SUCCESS,
            self::CANCELLED->value => self::CANCELLED,
            self::FAILED->value => self::FAILED,
            default => throw new InvalidArgumentException('Invalid payment status value'),
        };
    }

    public static function fromEnum(self $enum): self
    {
        return match ($enum) {
            self::PENDING => self::PENDING,
            self::INITIALISED => self::INITIALISED,
            self::SUCCESS => self::SUCCESS,
            self::CANCELLED => self::CANCELLED,
            self::FAILED => self::FAILED,
        };
    }

    public static function getElements(): array
    {
        return [
            self::PENDING->value,
            self::INITIALISED->value,
            self::SUCCESS->value,
            self::CANCELLED->value,
            self::FAILED->value,
        ];
    }
}
