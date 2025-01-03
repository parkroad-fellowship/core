<?php

namespace App\Helpers;

use Illuminate\Support\Str;

class Utils
{
    public static function generateUlid()
    {
        return strtolower((string) Str::ulid());
    }
}
