<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Enums\PRFActiveStatus;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\QueryBuilder\AllowedFilter;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class MissionType extends Model implements HasQueryBuilderCapabilities
{
    use BelongsToTenant;
    use HasFactory;
    use HasModelPermissions;
    use HasUlid;
    use LogsActivity;
    use SoftDeletes;

    public const INCLUDES = [
        'missions.schoolTerm',
        'missions.missionType',
        'missions.school',
        'missions.school.schoolContacts',
        'missions.school.schoolContacts.contactType',
        'missions.missionSubscriptions',
        'missions.missionSubscriptions.member',
        'missions.souls',
        'missions.loggedInMemberMissionSubscription',
        'missions.weatherForecasts',
        'missions.media',
        'missions.missionQuestions',
        'missions.missionSessions',
        'missions.accountingEvent',
        'missions.accountingEvent.allocationEntries',
        'missions.accountingEvent.refunds',
        'missions.accountingEvent.latestRefund',
        'missions.school.budgetEstimates',
        'missions.school.budgetEstimates.budgetEstimateEntries',
        'missions.school.budgetEstimates.budgetEstimateEntries.expenseCategory',
        'missions.requisitions',
        'missions.requisitions.requisitionItems',
        'missions.requisitions.requisitionItems.expenseCategory',
        'missions.offlineMembers',
    ];

    public const SORTS = ['created_at', 'updated_at'];

    /**
     * @return array<int, AllowedFilter>
     */
    public static function filters(): array
    {
        return [
            AllowedFilter::callback('status_key', function ($query, $value): void {
                $query->where('is_active', $value);
            }),
        ];
    }

    protected $fillable = [
        'ulid',
        'name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => PRFActiveStatus::class,
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

    public function missions()
    {
        return $this->hasMany(Mission::class);
    }

    /**
     * The mission type used as the default budget baseline (Sunday Service).
     *
     * Resolved from AppSetting 'budgets.fallback_mission_type' (ULID), falling
     * back to the type named 'Sunday Service'.
     */
    public static function defaultFallback(): ?self
    {
        $ulid = AppSetting::get('budgets.fallback_mission_type');

        if ($ulid) {
            $type = static::query()->where('ulid', $ulid)->first();

            if ($type) {
                return $type;
            }
        }

        return static::query()->where('name', 'Sunday Service')->first();
    }
}
