<?php

namespace App\Enums;

enum PRFLessonType: int
{
    case TEXT = 1;
    case VIDEO = 2;
    case AUDIO = 3;
    case DOCUMENT = 4;

    public static function getOptions(): array
    {
        return [
            self::TEXT->value => 'Text',
            self::VIDEO->value => 'Video',
            self::AUDIO->value => 'Audio',
            self::DOCUMENT->value => 'Document',

        ];
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::TEXT => 'Text',
            self::VIDEO => 'Video',
            self::AUDIO => 'Audio',
            self::DOCUMENT => 'Document',
        };
    }

    public static function fromValue(int $value): self
    {
        return match ($value) {
            self::TEXT->value => self::TEXT,
            self::VIDEO->value => self::VIDEO,
            self::AUDIO->value => self::AUDIO,
            self::DOCUMENT->value => self::DOCUMENT,
        };
    }

    public static function fromEnum(self $enum): self
    {
        return match ($enum) {
            self::TEXT => self::TEXT,
            self::VIDEO => self::VIDEO,
            self::AUDIO => self::AUDIO,
            self::DOCUMENT => self::DOCUMENT,
        };
    }

    public static function getElements(): array
    {
        return [
            self::TEXT->value,
            self::VIDEO->value,
            self::AUDIO->value,
            self::DOCUMENT->value,
        ];
    }
}
