<?php

namespace App\Models;

use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class RouteDistance extends Model
{
    use HasUlid;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'origin_latitude',
        'origin_longitude',
        'destination_latitude',
        'destination_longitude',
        'distance',
        'static_duration',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
