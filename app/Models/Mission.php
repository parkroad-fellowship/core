<?php

namespace App\Models;

use App\Enums\PRFMissionStatus;
use App\Enums\PRFMissionSubscriptionStatus;
use App\Enums\PRFMorphType;
use App\Observers\MissionObserver;
use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[ObservedBy(MissionObserver::class)]
class Mission extends Model implements HasMedia
{
    use HasFactory;
    use HasUlid;
    use InteractsWithMedia;
    use LogsActivity;
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
        'theme',
        'capacity',
        'mission_prep_notes',
        'status',
        'dressing_recommendations',
        'activity_recommendations',
        'weather_recommendations',
        'executive_summary',
        'whats_app_link',
        'teacher_feedback_requested_at',
        'offline_members',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'status' => 'integer',
        'offline_members' => 'array',
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
        'missionExpense',
        'missionExpense.expenses',
        'missionExpense.expenses.receipt',
        'weatherForecasts',
        'media',
        'missionSessions',
        'accountingEvent',
        'accountingEvent.refunds',
        'accountingEvent.latestRefund',
    ];

    protected $appends = [
        'mission_subscriptions_count',
        'mission_subscriptions_needed',
        'location',
    ];

    public const MEDIA_COLLECTIONS = [
        self::MISSION_PHOTOS,
        self::MISSION_FIT_CHECKS,
        self::MISSION_VIDEOS,
    ];

    public const MISSION_PHOTOS = 'mission-photos';

    public const MISSION_FIT_CHECKS = 'mission-fit-checks';

    public const MISSION_VIDEOS = 'mission-videos';

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

    public function missionQuestions()
    {
        return $this->hasMany(MissionQuestion::class);
    }

    public function missionExpense()
    {
        return $this->hasOne(MissionExpense::class);
    }

    public function expenses()
    {
        return $this
            ->hasManyThrough(
                related: Expense::class,
                through: MissionExpense::class,
                firstKey: 'mission_id',
                secondKey: 'expenseable_id',
                localKey: 'id',
                secondLocalKey: 'id',
            )
            ->where('expenseable_type', PRFMorphType::MISSION_EXPENSE->value);
    }

    public function weatherForecasts(): MorphMany
    {
        return $this->morphMany(
            related: WeatherForecast::class,
            name: 'weather_forecastable',
        );
    }

    public function loggedInMemberMissionSubscription()
    {
        return $this
            ->hasOne(MissionSubscription::class)
            ->where([
                'member_id' => Member::query()
                    ->where('user_id', Auth::id())
                    ->limit(1)
                    ->select('id'),
            ]);
    }

    public function getMissionSubscriptionsCountAttribute()
    {
        return $this->missionSubscriptions()
            ->whereIn('status', [PRFMissionSubscriptionStatus::APPROVED, PRFMissionSubscriptionStatus::PENDING])
            ->count() + count($this->offline_members);
    }

    public function getMissionSubscriptionsNeededAttribute()
    {
        return $this->capacity - ($this->missionSubscriptions()
            ->whereIn('status', [PRFMissionSubscriptionStatus::APPROVED])
            ->count() + count($this->offline_members));
    }

    public function getLocationAttribute()
    {
        $school = $this->school;

        return "{$school->latitude},{$school->longitude}";
    }

    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection(self::MISSION_PHOTOS)
            ->acceptsMimeTypes([
                'image/jpg',
                'image/jpeg',
                'image/tiff',
                'image/png',
                'image/heic',
                'image/heif',
            ]);

        $this
            ->addMediaCollection(self::MISSION_VIDEOS)
            ->acceptsMimeTypes([
                'video/mp4',
                'video/mpeg',
                'video/quicktime',
                'video/x-msvideo',
            ]);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

    public function missionSessions()
    {
        return $this->hasMany(MissionSession::class);
    }

    protected function statusLabel(): Attribute
    {
        return Attribute::get(fn () => PRFMissionStatus::fromValue($this->status)->getLabel());
    }

    public function missionPhotos(): MorphMany
    {
        return $this->media()->where('collection_name', self::MISSION_PHOTOS);
    }

    public function missionVideos(): MorphMany
    {
        return $this->media()->where('collection_name', self::MISSION_VIDEOS);
    }

    public function accountingEvent()
    {
        return $this->morphOne(
            related: AccountingEvent::class,
            name: 'accounting_eventable',
        );
    }

    public function requisitions()
    {
        return $this->hasManyThrough(
            related: Requisition::class,
            through: AccountingEvent::class,
            firstKey: 'accounting_eventable_id',
            secondKey: 'accounting_event_id',
        )->where('accounting_eventable_type', PRFMorphType::MISSION->value);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>=', now());
    }

    public function scopePast($query)
    {
        return $query->where('end_date', '<', now());
    }
}
