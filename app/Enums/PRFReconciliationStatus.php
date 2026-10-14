<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * The treasurer's monthly verdict on whether an accounting event's money is fully accounted for.
 */
enum PRFReconciliationStatus: int implements HasLabel, HasColor
{
    use HasEnumHelpers;

    case PENDING = 1;
    case FULLY_ACCOUNTED = 2;
    case NEEDS_ATTENTION = 3;

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::FULLY_ACCOUNTED => 'Fully accounted',
            self::NEEDS_ATTENTION => 'Needs attention',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PENDING => 'gray',
            self::FULLY_ACCOUNTED => 'success',
            self::NEEDS_ATTENTION => 'danger',
        };
    }
}
