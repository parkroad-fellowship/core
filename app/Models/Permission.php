<?php

namespace App\Models;

use App\Models\Concerns\HasCrossDomainConnection;

class Permission extends \Spatie\Permission\Models\Permission
{
    use HasCrossDomainConnection;
}
