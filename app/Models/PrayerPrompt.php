<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Enums\PRFActiveStatus;
use App\Enums\PRFPromptFrequency;
use App\Enums\PRFPromptTime;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\QueryBuilder\AllowedFilter;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class PrayerPrompt extends Model implements HasQueryBuilderCapabilities
{
    use BelongsToTenant;
    use HasFactory;
    use HasModelPermissions;
    use HasUlid;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'description',
        'frequency',
        'day_of_week',
        'time_of_day',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => PRFActiveStatus::class,
            'frequency' => PRFPromptFrequency::class,
            'time_of_day' => PRFPromptTime::class,
        ];
    }

    const INCLUDES = [
        'prayerResponses',
    ];

    public const SORTS = ['created_at', 'updated_at'];

    /**
     * @return array<int, string|AllowedFilter>
     */
    public static function filters(): array
    {
        return [
            AllowedFilter::callback('is_active', function ($query, $value) {
                $query->where('is_active', $value);
            }),
        ];
    }

    public function prayerResponses()
    {
        return $this->hasMany(PrayerResponse::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
