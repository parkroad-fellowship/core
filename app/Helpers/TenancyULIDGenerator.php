<?php

declare(strict_types=1);

namespace App\Helpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Stancl\Tenancy\Contracts\UniqueIdentifierGenerator;

/**
 * Generates a ULID for the tenant key. Replaces the default ULID generator (UniqueIdentifierGenerators\ULIDGenerator) to ensure that the generated ULID is in lowercase.
 */
class TenancyULIDGenerator implements UniqueIdentifierGenerator
{
    public static function generate(Model $model): string|int
    {
        return Str::lower(Str::ulid()->toString());
    }
}
