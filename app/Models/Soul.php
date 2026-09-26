<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Enums\PRFSoulDecisionType;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasULID;
use Database\Factories\SoulFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\QueryBuilder\AllowedFilter;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

/**
 * @property-read ?Mission $mission
 * @property-read ?ClassGroup $classGroup
 * @property ?PRFSoulDecisionType $decision_type
 */
#[Fillable([
    'ulid',
    'mission_id',
    'class_group_id',
    'full_name',
    'admission_number',
    'decision_type',
    'notes',
])]
class Soul extends Model implements HasQueryBuilderCapabilities
{
    use BelongsToTenant;
    /** @use HasFactory<SoulFactory> */
    use HasFactory;
    use HasModelPermissions;
    use HasULID;
    use LogsActivity;
    use SoftDeletes;

    public const INCLUDES = [
        'mission',
        'classGroup',
    ];

    public const SORTS = ['created_at', 'updated_at'];

    protected function casts(): array
    {
        return [
            'decision_type' => PRFSoulDecisionType::class,
        ];
    }

    /**
     * @return array<int, string|AllowedFilter>
     */
    public static function filters(): array
    {
        return [
            AllowedFilter::callback('mission_ulid', function ($query, $value) {
                $query->where('mission_id', Mission::query()->select('id')->where('ulid', $value)->limit(1));
            }),
            AllowedFilter::callback('class_group_ulid', function ($query, $value) {
                $query->where('class_group_id', ClassGroup::query()->select('id')->where('ulid', $value)->limit(1));
            }),
        ];
    }

    /**
     * @return BelongsTo<Mission, $this>
     */
    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    /**
     * @return BelongsTo<ClassGroup, $this>
     */
    public function classGroup(): BelongsTo
    {
        return $this->belongsTo(ClassGroup::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
