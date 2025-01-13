<?php

namespace App\Models;

use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PrayerPrompt extends Model
{
    use HasFactory;
    use HasUlid;
    use SoftDeletes;
    use LogsActivity;

    protected $fillable = [
        'description',
        'frequency',
        'day_of_week',
        'time_of_day',
        'is_active',
    ];

    const INCLUDES = [
        'prayerResponses',
    ];

    public function prayerResponses()
    {
        return $this->hasMany(PrayerResponse::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
