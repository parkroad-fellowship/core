<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PRFDeliveryStatus: int implements HasLabel, HasColor
{
    use HasEnumHelpers;

    case PENDING = 1;
    case SENT = 2;
    case FAILED = 3;

    /** WhatsApp receipts are sent by the treasurer from their own phone through a share link. */
    case LINK_READY = 4;

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::SENT => 'Sent',
            self::FAILED => 'Failed',
            self::LINK_READY => 'Share link ready',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PENDING => 'gray',
            self::SENT => 'success',
            self::FAILED => 'danger',
            self::LINK_READY => 'info',
        };
    }
}
