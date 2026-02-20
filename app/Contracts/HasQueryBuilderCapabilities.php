<?php

namespace App\Contracts;

interface HasQueryBuilderCapabilities
{
    /** @var list<string> */
    public const INCLUDES = [];

    /** @var list<string> */
    public const SORTS = [];

    /** @return array<int, \Spatie\QueryBuilder\AllowedFilter> */
    public static function filters(): array;
}
