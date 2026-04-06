<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Enums\PRFMissionRole;
use App\Enums\PRFMissionSubscriptionStatus;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasUlid;
use App\Observers\MissionSubscriptionObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\QueryBuilder\AllowedFilter;

#[ObservedBy(MissionSubscriptionObserver::class)]
class MissionSubscription extends Model implements HasQueryBuilderCapabilities
{
    use HasFactory;
    use HasModelPermissions;
    use HasUlid;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'mission_id',
        'member_id',
        'status',
        'mission_role',
        'invited_to_group',
        'invited_to_group_at',
        'notes',
    ];

    const INCLUDES = [
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
                $query->where(
                    'mission_id',
                    Mission::query()
                        ->select('id')
                        ->where('ulid', $value)
                        ->limit(1)
                );
            }),
            AllowedFilter::callback('member_ulid', function ($query, $value) {
                $query->where(
                    'member_id',
                    Member::query()
                        ->select('id')
                        ->where('ulid', $value)
                        ->limit(1)
                );
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

    protected $appends = [
        'mission_subscription_status',
        'status_label',
        'mission_role_label',
    ];

    public $casts = [
        'notes' => 'array',
    ];

    public function mission()
    {
        return $this->belongsTo(Mission::class);
    }

    public function member()
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
        return Attribute::get(fn () => PRFMissionSubscriptionStatus::fromValue($this->status)->getLabel());
    }

    protected function missionRoleLabel(): Attribute
    {
        return Attribute::get(fn () => PRFMissionRole::fromValue($this->mission_role)->getLabel());
    }

    public function getMissionSubscriptionStatusAttribute(): PRFMissionSubscriptionStatus
    {
        // If $this->status is already an enum instance, return it directly
        if ($this->status instanceof PRFMissionSubscriptionStatus) {
            return $this->status;
        }

        // Otherwise, convert from int/string to enum
        return PRFMissionSubscriptionStatus::from($this->status);
    }
}
