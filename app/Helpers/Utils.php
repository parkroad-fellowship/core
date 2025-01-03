<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class Utils
{
    static function generateUlid()
    {
        return strtolower((string) Str::ulid());
    }
}

