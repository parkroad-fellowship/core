<?php

namespace App\Models;

use App\Models\Concerns\HasCrossDomainConnection;
use Illuminate\Database\Eloquent\SoftDeletes;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Role extends \Spatie\Permission\Models\Role
{
    use BelongsToTenant;
    use HasCrossDomainConnection;
    use SoftDeletes;
}
