<?php

namespace App\Enums;

enum PRFEnvironment: string
{
    case LOCAL = 'local';
    case DEVELOPMENT = 'development';
    case STAGING = 'staging';
    case PRODUCTION = 'production';

    public static function fromEnv(string $value): self
    {
        return match ($value) {
            'local' => self::LOCAL,
            'development' => self::DEVELOPMENT,
            'staging' => self::STAGING,
            'production' => self::PRODUCTION,
        };
    }
}
