<?php

namespace App\Models\Concerns;

use App\Helpers\Utils;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

trait HasULID
{
    /**
     * Boot the HasULID trait.
     */
    public static function bootHasULID(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->ulid)) {
                $model->ulid = Utils::generateULID();
            }
        });
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    /**
     * Resolve the route binding query with ULID format validation.
     *
     * @param  Builder<static>  $query
     * @param  mixed  $value
     * @param  string|null  $field
     * @return \Illuminate\Database\Eloquent\Relations\Relation<static, *, *>|\Illuminate\Database\Eloquent\Builder<static>
     */
    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        if ($field === null || $field === 'ulid') {
            if (!Str::isUlid($value)) {
                throw new ModelNotFoundException()->setModel(static::class, [$value]);
            }
        }

        return parent::resolveRouteBindingQuery($query, $value, $field);
    }

    /**
     * Find a model by its ULID.
     */
    public static function findByULID(string $ulid): ?static
    {
        return static::query()->where('ulid', $ulid)->first();
    }
}
