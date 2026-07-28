<?php

namespace App\Models;

use App\Models\Concerns\HasCrossDomainConnection;
use Spatie\Activitylog\Models\Activity as SpatieActivity;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Activity extends SpatieActivity
{
    use BelongsToTenant;
    use HasCrossDomainConnection;
}
