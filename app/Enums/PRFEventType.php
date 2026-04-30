<?php

namespace App\Enums;

enum PRFEventType: int
{
    case MEMBER = 1; // Broadcast to members
    case LEADERSHIP = 2; // For internal purposes

    public static function getOptions(): array
    {
        return [
            self::MEMBER->value => 'Member (Broadcast to members)',
            self::LEADERSHIP->value => 'Leadership (For internal purposes)',
        ];
    }
}
