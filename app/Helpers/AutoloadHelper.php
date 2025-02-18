<?php

use Illuminate\Support\Facades\Auth;

function userCan(string $ability): bool
{
    return Auth::user()->can($ability);
}
