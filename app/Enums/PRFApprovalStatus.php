<?php

namespace App\Enums;

use Filament\Tables\Filters\SelectFilter;

enum PRFApprovalStatus: int
{
    case PENDING = 1;
    case APPROVED = 2;
    case REJECTED = 3;
    case UNDER_REVIEW = 4;

    public static function getOptions(): array
    {
        return [
            self::PENDING->value => 'Pending Approval',
            self::APPROVED->value => 'Approved',
            self::REJECTED->value => 'Rejected',
            self::UNDER_REVIEW->value => 'Under Review',
        ];
    }

    public static function getFilterOptions(): array
    {
        return [
            self::PENDING->value => 'Pending Approval',
            self::APPROVED->value => 'Approved',
            self::REJECTED->value => 'Rejected',
            self::UNDER_REVIEW->value => 'Under Review',
        ];
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Pending Approval',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
            self::UNDER_REVIEW => 'Under Review',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
            self::UNDER_REVIEW => 'info',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::PENDING => 'heroicon-o-clock',
            self::APPROVED => 'heroicon-o-check-circle',
            self::REJECTED => 'heroicon-o-x-circle',
            self::UNDER_REVIEW => 'heroicon-o-eye',
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
            self::UNDER_REVIEW->value => self::UNDER_REVIEW,
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

    public function isUnderReview(): bool
    {
        return $this === self::UNDER_REVIEW;
    }

    public function requiresApprover(): bool
    {
        return in_array($this, [self::APPROVED, self::REJECTED, self::UNDER_REVIEW]);
    }

    public function requiresApprovalDate(): bool
    {
        return in_array($this, [self::APPROVED, self::REJECTED]);
    }
}
