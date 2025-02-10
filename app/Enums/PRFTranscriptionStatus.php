<?php

namespace App\Enums;

enum PRFTranscriptionStatus: int
{
    case NOT_STARTED = 1;
    case RUNNING = 2;
    case SUCCEEDED = 3;
    case FAILED = 4;

    public static function getElements(): array
    {
        return [
            self::NOT_STARTED,
            self::RUNNING,
            self::FAILED,
            self::SUCCEEDED,
        ];
    }

    public static function fromValue(string $value): self
    {
        return match ($value) {
            'NotStarted' => self::NOT_STARTED,
            'Running' => self::RUNNING,
            'Failed' => self::FAILED,
            'Succeeded' => self::SUCCEEDED,
        };
    }

    public static function getOptions(): array
    {
        return [
            self::NOT_STARTED->value => 'Not Started',
            self::RUNNING->value => 'Running',
            self::FAILED->value => 'Failed',
            self::SUCCEEDED->value => 'Succeeded',
        ];
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
