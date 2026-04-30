<?php

namespace App\Contracts;

use Spatie\QueryBuilder\AllowedFilter;

interface HasQueryBuilderCapabilities
{
    /** @var list<string> */
    public const INCLUDES = [];

    /** @var list<string> */
    public const SORTS = [];

    /** @return array<int, AllowedFilter> */
    public static function filters(): array;
}
