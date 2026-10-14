<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

/**
 * Where an income receipt is delivered.
 */
enum PRFReceiptChannel: int implements HasLabel, HasIcon
{
    use HasEnumHelpers;

    case EMAIL = 1;
    case SMS = 2;
    case WHATSAPP = 3;

    public function getLabel(): string
    {
        return match ($this) {
            self::EMAIL => 'Email',
            self::SMS => 'SMS',
            self::WHATSAPP => 'WhatsApp',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::EMAIL => 'heroicon-o-envelope',
            self::SMS => 'heroicon-o-chat-bubble-left',
            self::WHATSAPP => 'heroicon-o-chat-bubble-left-right',
        };
    }
}
