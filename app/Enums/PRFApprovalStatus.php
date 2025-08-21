<?php

namespace App\Enums;

use Filament\Tables\Filters\SelectFilter;

enum PRFApprovalStatus: int
{
    case PENDING = 1;
    case UNDER_REVIEW = 2;
    case APPROVED = 3;
    case REJECTED = 4;

    public static function getOptions(): array
    {
        return [
            self::PENDING->value => 'Pending',
            self::UNDER_REVIEW->value => 'Under Review',
            self::APPROVED->value => 'Approved',
            self::REJECTED->value => 'Rejected',
        ];
    }

    public static function getFilterOptions(): array
    {
        return [
            self::PENDING->value => 'Pending',
            self::UNDER_REVIEW->value => 'Under Review',
            self::APPROVED->value => 'Approved',
            self::REJECTED->value => 'Rejected',
        ];
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::UNDER_REVIEW => 'Under Review',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::PENDING => 'heroicon-o-clock',
            self::APPROVED => 'heroicon-o-check-circle',
            self::REJECTED => 'heroicon-o-x-circle',
        };
    }

    public static function getTableFilter(string $column = 'approval_status'): SelectFilter
    {
        return SelectFilter::make($column)
            ->label('Approval Status')
            ->options(self::getFilterOptions())
            ->placeholder('All statuses')
            ->native(false);
    }

    public static function fromValue(int $value): self
    {
        return match ($value) {
            self::PENDING->value => self::PENDING,
            self::APPROVED->value => self::APPROVED,
            self::REJECTED->value => self::REJECTED,
            default => self::PENDING,
        };
    }

    public function isApproved(): bool
    {
        return $this === self::APPROVED;
    }

    public function isRejected(): bool
    {
        return $this === self::REJECTED;
    }

    public function isPending(): bool
    {
        return $this === self::PENDING;
    }

    public function requiresApprover(): bool
    {
        return in_array($this, [self::APPROVED, self::REJECTED]);
    }

    public function requiresApprovalDate(): bool
    {
        return in_array($this, [self::APPROVED, self::REJECTED]);
    }
}
