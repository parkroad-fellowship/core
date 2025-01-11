<?php

namespace App\Models;

use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RouteDistance extends Model
{
    use HasUlid;
    use SoftDeletes;

    protected $fillable = [
        'origin_latitude',
        'origin_longitude',
        'destination_latitude',
        'destination_longitude',
        'distance',
        'static_duration',
    ];
}
