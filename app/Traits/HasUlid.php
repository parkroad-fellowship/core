<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait HasUlid
{
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->ulid = strtolower((string) Str::ulid());
        });
    }
}
