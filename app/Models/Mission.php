<?php

namespace App\Models;

use App\Enums\PRFMissionSubscriptionStatus;
use App\Observers\MissionObserver;
use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(MissionObserver::class)]
class Mission extends Model
{
    use HasFactory;
    use HasUlid;
    use SoftDeletes;

    protected $fillable = [
        'ulid',
        'school_term_id',
        'mission_type_id',
        'school_id',
        'start_date',
        'start_time',
        'end_date',
        'end_time',
        'capacity',
        'mission_prep_notes',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    const INCLUDES = [
        'schoolTerm',
        'missionType',
        'school',
        'school.schoolContacts',
        'school.schoolContacts.contactType',
        'missionSubscriptions',
        'missionSubscriptions.member',
        'souls',
        'loggedInMemberMissionSubscription',
    ];

    protected $appends = [
        'mission_subscriptions_needed',
    ];

    public function schoolTerm()
    {
        return $this->belongsTo(SchoolTerm::class);
    }

    public function missionType()
    {
        return $this->belongsTo(MissionType::class);
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function missionSubscriptions()
    {
        return $this->hasMany(MissionSubscription::class);
    }

    public function souls()
    {
        return $this->hasMany(Soul::class);
    }

    public function debriefNotes()
    {
        return $this->hasMany(DebriefNote::class);
    }

    public function cohortMissions()
    {
        return $this->hasMany(CohortMission::class);
    }

    public function loggedInMemberMissionSubscription()
    {
        return $this
            ->hasOne(MissionSubscription::class)
            ->where([
                'member_id' => Member::query()
                    ->where('user_id', auth()->id())
                    ->limit(1)
                    ->select('id'),
            ]);
    }

    public function getMissionSubscriptionsNeededAttribute()
    {
        return $this->capacity - $this->missionSubscriptions()
            ->whereIn('status', [PRFMissionSubscriptionStatus::APPROVED])
            ->count();
    }
}
