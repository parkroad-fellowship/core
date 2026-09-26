<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Enums\PRFMissionRole;
use App\Enums\PRFMissionSubscriptionStatus;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasULID;
use App\Observers\MissionSubscriptionObserver;
use Database\Factories\MissionSubscriptionFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\QueryBuilder\AllowedFilter;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

/**
 * @property PRFMissionSubscriptionStatus $status
 * @property ?PRFMissionRole $mission_role
 * @property-read ?Member $member
 * @property-read ?Mission $mission
 */
#[Fillable([
    'mission_id',
    'member_id',
    'status',
    'mission_role',
    'invited_to_group',
    'invited_to_group_at',
    'notes',
])]
#[Appends([
    'mission_subscription_status',
    'status_label',
    'mission_role_label',
])]
#[ObservedBy(MissionSubscriptionObserver::class)]
class MissionSubscription extends Model implements HasQueryBuilderCapabilities
{
    use BelongsToTenant;
    /** @use HasFactory<MissionSubscriptionFactory> */
    use HasFactory;
    use HasModelPermissions;
    use HasULID;
    use LogsActivity;
    use SoftDeletes;

    public const INCLUDES = [
        'mission',
        'mission.school',
        'mission.schoolTerm',
        'mission.missionType',
        'mission.weatherForecasts',
        'mission.school.schoolContacts.contactType',
        'member',
        'member.profilePicture',
        'mission.loggedInMemberMissionSubscription',
        'mission.accountingEvent',
    ];

    public const SORTS = ['created_at', 'updated_at'];

    /**
     * @return array<int, AllowedFilter>
     */
    public static function filters(): array
    {
        return [
            AllowedFilter::callback('mission_ulid', function ($query, $value) {
                $query->where('mission_id', Mission::query()->select('id')->where('ulid', $value)->limit(1));
            }),
            AllowedFilter::callback('member_ulid', function ($query, $value) {
                $query->where('member_id', Member::query()->select('id')->where('ulid', $value)->limit(1));
            }),
            AllowedFilter::callback('status_key', function ($query, $value) {
                $query->where('status', $value);
            }),
            AllowedFilter::callback('status_keys', function ($query, $value) {
                $query->whereIn('status', Arr::wrap($value));
            }),
            AllowedFilter::scope('upcoming'),
            AllowedFilter::scope('past'),
            AllowedFilter::callback('search', function ($query, $value) {
                $query->whereHas('mission', function ($query) use ($value) {
                    $query->where(function ($query) use ($value) {
                        $query
                            ->whereLike('theme', "%{$value}%")
                            ->orWhereHas('school', function ($query) use ($value) {
                                $query->whereLike('name', "%{$value}%");
                            })
                            ->orWhereHas('missionType', function ($query) use ($value) {
                                $query->whereLike('name', "%{$value}%");
                            });
                    });
                });
            }),
        ];
    }

    protected function casts(): array
    {
        return [
            'notes' => 'array',
            'status' => PRFMissionSubscriptionStatus::class,
            'mission_role' => PRFMissionRole::class,
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
     * @return BelongsTo<Member, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function scopeUpcoming($query)
    {
        return $query->whereHas('mission', function ($query) {
            $query->where('start_date', '>=', now()->toDateString());
        });
    }

    public function scopePast($query)
    {
        return $query->whereHas('mission', function ($query) {
            $query->where('start_date', '<', now()->toDateString());
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

    protected function statusLabel(): Attribute
    {
        return Attribute::get(fn() => $this->status->getLabel());
    }

    protected function missionRoleLabel(): Attribute
    {
        return Attribute::get(fn() => $this->mission_role?->getLabel());
    }

    public function getMissionSubscriptionStatusAttribute(): ?PRFMissionSubscriptionStatus
    {
        return $this->status;
    }
}
