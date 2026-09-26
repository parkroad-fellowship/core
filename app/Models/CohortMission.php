<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasULID;
use Database\Factories\CohortMissionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

#[Fillable([
    'ulid',
    'cohort_id',
    'mission_id',
])]
class CohortMission extends Model implements HasQueryBuilderCapabilities
{
    use BelongsToTenant;
    /** @use HasFactory<CohortMissionFactory> */
    use HasFactory;
    use HasModelPermissions;
    use HasULID;
    use LogsActivity;
    use SoftDeletes;

    public const INCLUDES = [
        'cohort',
        'mission',
    ];

    public const SORTS = ['created_at', 'updated_at'];

    /**
     * @return BelongsTo<Cohort, $this>
     */
    public function cohort(): BelongsTo
    {
        return $this->belongsTo(Cohort::class);
    }

    /**
     * @return BelongsTo<Mission, $this>
     */
    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public static function filters(): array
    {
        return [];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
