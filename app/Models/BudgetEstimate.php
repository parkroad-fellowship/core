<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Enums\PRFActiveStatus;
use App\Enums\PRFMorphType;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\QueryBuilder\AllowedFilter;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class BudgetEstimate extends Model implements HasQueryBuilderCapabilities
{
    use BelongsToTenant;
    use HasModelPermissions;
    use HasUlid;
    use SoftDeletes;

    protected $fillable = [
        'ulid',
        'budget_estimatable_id',
        'budget_estimatable_type',
        'mission_type_id',
        'grand_total',
        'baseline_people',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'grand_total' => 'integer',
            'baseline_people' => 'integer',
            'is_active' => PRFActiveStatus::class,
            'budget_estimatable_type' => PRFMorphType::class,
        ];
    }

    public const INCLUDES = [
        'budgetEstimatable',
        'missionType',
        'budgetEstimateEntries',
        'budgetEstimateEntries.expenseCategory',
    ];

    public const SORTS = ['created_at', 'updated_at', 'grand_total', 'baseline_people'];

    public static function filters(): array
    {
        return [
            AllowedFilter::callback('mission_type_ulid', function (Builder $query, $value): void {
                $query->where('mission_type_id', MissionType::query()->select('id')->where('ulid', $value)->limit(1));
            }),
            AllowedFilter::callback('status_key', function (Builder $query, $value): void {
                $query->where('is_active', $value);
            }),
        ];
    }

    public function budgetEstimatable()
    {
        return $this->morphTo();
    }

    public function missionType(): BelongsTo
    {
        return $this->belongsTo(MissionType::class);
    }

    public function budgetEstimateEntries()
    {
        return $this->hasMany(BudgetEstimateEntry::class);
    }

    /**
     * Resolve the ACTIVE estimate for a school and mission type.
     *
     * Falls back to the school's estimate for the default fallback mission type
     * (Sunday Service) when no exact type match exists.
     */
    public static function forSchoolAndType(int $schoolId, int $missionTypeId): ?self
    {
        $estimate = static::query()
            ->where([
                'is_active' => PRFActiveStatus::ACTIVE,
                'budget_estimatable_type' => PRFMorphType::SCHOOL,
                'budget_estimatable_id' => $schoolId,
                'mission_type_id' => $missionTypeId,
            ])
            ->first();

        if ($estimate) {
            return $estimate;
        }

        if ($missionTypeId === static::fallbackMissionTypeId()) {
            return null;
        }

        $fallbackTypeId = static::fallbackMissionTypeId();

        if (!$fallbackTypeId) {
            return null;
        }

        return static::query()
            ->where([
                'is_active' => PRFActiveStatus::ACTIVE,
                'budget_estimatable_type' => PRFMorphType::SCHOOL,
                'budget_estimatable_id' => $schoolId,
                'mission_type_id' => $fallbackTypeId,
            ])
            ->first();
    }

    public static function fallbackMissionTypeId(): ?int
    {
        return once(fn() => MissionType::defaultFallback()?->id);
    }
}
