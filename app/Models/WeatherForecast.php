<?php

namespace App\Models;

use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class WeatherForecast extends Model
{
    /** @use HasFactory<\Database\Factories\WeatherForecastFactory> */
    use HasFactory;

    use HasUlid;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'mission_id',
        'forecast_date',
        'weather_code',
        'weather_code_description',
        'moon_rise_time',
        'moon_set_time',
        'sun_rise_time',
        'sun_set_time',
        'cloud_cover',
        'dew_point',
        'humidity',
        'precipitation_probability',
        'rain',
        'temperature',
        'uv',
        'visibility',
        'wind',
        'forecast_data',
        'dressing_recommendations',
        'activity_recommendations',
        'weather_recommendations',
        'weather_forecastable_id',
        'weather_forecastable_type',
    ];

    protected function casts(): array
    {
        return [
            'cloud_cover' => 'array',
            'dew_point' => 'array',
            'humidity' => 'array',
            'precipitation_probability' => 'array',
            'rain' => 'array',
            'temperature' => 'array',
            'uv' => 'array',
            'visibility' => 'array',
            'wind' => 'array',
            'forecast_data' => 'array',
        ];
    }

    const INCLUDES = [
        'mission',
    ];

    public function mission()
    {
        return $this->belongsTo(Mission::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

    public function weatherForecastable()
    {
        return $this->morphTo();
    }
}
