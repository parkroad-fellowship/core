<?php

use Illuminate\Support\Facades\Auth;

function userCan(string $ability)
{
    return Auth::user()->can($ability);
}
