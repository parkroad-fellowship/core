<?php

namespace App\Models;

use App\Enums\PRFMissionSubscriptionStatus;
use App\Observers\MissionSubscriptionObserver;
use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

#[ObservedBy(MissionSubscriptionObserver::class)]
class MissionSubscription extends Model
{
    use HasFactory;
    use HasUlid;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'mission_id',
        'member_id',
        'status',
        'mission_role',
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
    ];

    protected $appends = [
        'mission_subscription_status',
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
