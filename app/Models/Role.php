<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Role extends \Spatie\Permission\Models\Role
{
    use BelongsToTenant;
    use SoftDeletes;
}
