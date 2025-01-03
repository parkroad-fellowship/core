<?php

namespace App\Traits;

use App\Helpers\Utils;
use Illuminate\Support\Str;

trait HasUlid
{
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->ulid = Utils::generateUlid();
        });
    }
}
