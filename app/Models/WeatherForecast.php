<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Enums\PRFMorphType;
use App\Models\Concerns\HasULID;
use Database\Factories\WeatherForecastFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\QueryBuilder\AllowedFilter;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

#[Fillable([
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
])]
class WeatherForecast extends Model implements HasQueryBuilderCapabilities
{
    use BelongsToTenant;
    /** @use HasFactory<WeatherForecastFactory> */
    use HasFactory;

    use HasULID;
    use LogsActivity;
    use SoftDeletes;

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
            'forecast_date' => 'datetime',
            'weather_forecastable_type' => PRFMorphType::class,
        ];
    }

    public const INCLUDES = [
        'mission',
    ];

    public const SORTS = ['created_at', 'updated_at'];

    /**
     * @return array<int, AllowedFilter>
     */
    public static function filters(): array
    {
        return [];
    }

    /**
     * @return BelongsTo<Mission, $this>
     */
    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function weatherForecastable(): MorphTo
    {
        return $this->morphTo();
    }
}
