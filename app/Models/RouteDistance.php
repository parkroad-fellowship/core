<?php

namespace App\Models;

use App\Models\Concerns\HasULID;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

#[Fillable([
    'origin_latitude',
    'origin_longitude',
    'destination_latitude',
    'destination_longitude',
    'distance',
    'static_duration',
])]
class RouteDistance extends Model
{
    use BelongsToTenant;
    use HasULID;
    use LogsActivity;
    use SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
