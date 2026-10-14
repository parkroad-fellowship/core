<?php

namespace App\Jobs\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Requests reference related records by ULID; the database stores ids.
 */
trait ResolvesULIDs
{
    /**
     * Replace each `{name}_ulid` key present in $data with `{name}_id` (or the given id column).
     * A null ULID clears the relation; an unknown ULID fails with a 404.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, class-string<Model>|array{0: class-string<Model>, 1: string}>  $relations  ULID key => model class, or [model class, id column]
     * @return array<string, mixed>
     */
    protected function resolveULIDs(array $data, array $relations): array
    {
        foreach ($relations as $ulidKey => $relation) {
            if (!array_key_exists($ulidKey, $data)) {
                continue;
            }

            [$modelClass, $idKey] = is_array($relation)
                ? $relation
                : [$relation, Str::replaceLast('_ulid', '_id', $ulidKey)];

            $ulid = $data[$ulidKey];
            unset($data[$ulidKey]);

            $data[$idKey] = $ulid === null ? null : $modelClass::query()->where('ulid', $ulid)->firstOrFail()->getKey();
        }

        return $data;
    }
}
