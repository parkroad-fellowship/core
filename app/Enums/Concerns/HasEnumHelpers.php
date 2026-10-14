<?php

namespace App\Enums\Concerns;

/**
 * Standard helpers for int-backed enums that define getLabel().
 */
trait HasEnumHelpers
{
    /**
     * @return array<int, string>
     */
    public static function getOptions(): array
    {
        return collect(self::cases())->mapWithKeys(fn(self $case) => [$case->value => $case->getLabel()])->all();
    }

    /**
     * @return list<int>
     */
    public static function getElements(): array
    {
        return array_column(self::cases(), 'value');
    }
}
