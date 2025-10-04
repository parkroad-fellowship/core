<?php

namespace App\Enums;

use Filament\Tables\Filters\SelectFilter;

enum PRFPaymentMethod: int
{
    case MPESA = 1;
    case BANK_TRANSFER = 2;
    case PAYBILL = 3;
    case TILL_NUMBER = 4;

    public static function getOptions(): array
    {
        return [
            self::MPESA->value => 'MPESA',
            self::BANK_TRANSFER->value => 'Bank Transfer',
            self::PAYBILL->value => 'Paybill',
            self::TILL_NUMBER->value => 'Till Number',
        ];
    }

    public static function getFilterOptions(): array
    {
        return [
            self::MPESA->value => 'MPESA',
            self::BANK_TRANSFER->value => 'Bank Transfer',
            self::PAYBILL->value => 'Paybill',
            self::TILL_NUMBER->value => 'Till Number',
        ];
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::MPESA => 'MPESA',
            self::BANK_TRANSFER => 'Bank Transfer',
            self::PAYBILL => 'Paybill',
            self::TILL_NUMBER => 'Till Number',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::MPESA => 'success',
            self::BANK_TRANSFER => 'info',
            self::PAYBILL => 'warning',
            self::TILL_NUMBER => 'primary',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::MPESA => 'heroicon-o-device-phone-mobile',
            self::BANK_TRANSFER => 'heroicon-o-building-library',
            self::PAYBILL => 'heroicon-o-credit-card',
            self::TILL_NUMBER => 'heroicon-o-credit-card',
        };
    }

    public static function getTableFilter(string $column = 'payment_method'): SelectFilter
    {
        return SelectFilter::make($column)
            ->label('Payment Method')
            ->options(self::getFilterOptions())
            ->placeholder('All methods')
            ->native(false);
    }

    public static function fromValue(int $value): self
    {
        return match ($value) {
            self::MPESA->value => self::MPESA,
            self::BANK_TRANSFER->value => self::BANK_TRANSFER,
            self::PAYBILL->value => self::PAYBILL,
            self::TILL_NUMBER->value => self::TILL_NUMBER,
            default => self::MPESA,
        };
    }
}
