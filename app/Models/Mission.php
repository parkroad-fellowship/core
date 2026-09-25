<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Enums\PRFMissionStatus;
use App\Enums\PRFMissionSubscriptionStatus;
use App\Enums\PRFMorphType;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasULID;
use App\Observers\MissionObserver;
use Database\Factories\MissionFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\QueryBuilder\AllowedFilter;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

#[Fillable([
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
])]
#[Appends([
    'location',
])]
#[ObservedBy(MissionObserver::class)]
class Mission extends Model implements HasMedia, HasQueryBuilderCapabilities
{
    use BelongsToTenant;
    /** @use HasFactory<MissionFactory> */
    use HasFactory;
    use HasModelPermissions;
    use HasULID;
    use InteractsWithMedia;
    use LogsActivity;
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => PRFMissionStatus::class,
        ];
    }

    /**
     * Loaded with every query so seat counts never cost a query per mission.
     *
     * @var list<string>
     */
    protected $withCount = ['activeSubscriptions', 'approvedSubscriptions', 'offlineMembers'];

    public const INCLUDES = [
        'schoolTerm',
        'missionType',
        'school',
        'school.schoolContacts',
        'school.schoolContacts.contactType',
        'missionSubscriptions',
        'missionSubscriptions.member',
        'souls',
        'loggedInMemberMissionSubscription',
        'weatherForecasts',
        'media',
        'missionQuestions',
        'missionSessions',
        'accountingEvent',
        'accountingEvent.allocationEntries',
        'accountingEvent.refunds',
        'accountingEvent.latestRefund',
        'school.budgetEstimates',
        'school.budgetEstimates.budgetEstimateEntries',
        'school.budgetEstimates.budgetEstimateEntries.expenseCategory',
        'requisitions',
        'requisitions.requisitionItems',
        'requisitions.requisitionItems.expenseCategory',
        'offlineMembers',
    ];

    public const SORTS = ['created_at', 'updated_at', 'start_date'];

    /**
     * @return array<int, AllowedFilter>
     */
    public static function filters(): array
    {
        return [
            AllowedFilter::exact('ulid'),
            AllowedFilter::callback('school_term_ulid', function ($query, $value) {
                $query->where('school_term_id', SchoolTerm::query()->select('id')->where('ulid', $value)->limit(1));
            }),
            AllowedFilter::callback('mission_type_ulid', function ($query, $value) {
                $query->where('mission_type_id', MissionType::query()->select('id')->where('ulid', $value)->limit(1));
            }),
            AllowedFilter::callback('school_ulid', function ($query, $value) {
                $query->where('school_id', School::query()->select('id')->where('ulid', $value)->limit(1));
            }),
            AllowedFilter::callback('status_key', function ($query, $value) {
                $query->where('status', $value);
            }),
            AllowedFilter::callback('status_keys', function ($query, $value) {
                $query->whereIn('status', Arr::wrap($value));
            }),
            AllowedFilter::callback('unsubscribed', function ($query) {
                $query->whereDoesntHave('missionSubscriptions', function ($query) {
                    $query->where('member_id', Member::currentMemberIdQuery());
                });
            }),
            AllowedFilter::scope('upcoming'),
            AllowedFilter::scope('past'),
            AllowedFilter::callback('search', function ($query, $value) {
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
            }),
        ];
    }

    public const MEDIA_COLLECTIONS = [
        self::MISSION_PHOTOS,
        self::MISSION_FIT_CHECKS,
        self::MISSION_VIDEOS,
    ];

    public const MISSION_PHOTOS = 'mission-photos';

    public const MISSION_FIT_CHECKS = 'mission-fit-checks';

    public const MISSION_VIDEOS = 'mission-videos';

    /**
     * @return BelongsTo<SchoolTerm, $this>
     */
    public function schoolTerm(): BelongsTo
    {
        return $this->belongsTo(SchoolTerm::class);
    }

    /**
     * @return BelongsTo<MissionType, $this>
     */
    public function missionType(): BelongsTo
    {
        return $this->belongsTo(MissionType::class);
    }

    /**
     * @return BelongsTo<School, $this>
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * @return HasMany<MissionSubscription, $this>
     */
    public function missionSubscriptions(): HasMany
    {
        return $this->hasMany(MissionSubscription::class);
    }

    /**
     * Subscriptions that hold a seat (approved or awaiting approval).
     *
     * @return HasMany<MissionSubscription, $this>
     */
    public function activeSubscriptions(): HasMany
    {
        return $this->missionSubscriptions()->whereIn('status', [
            PRFMissionSubscriptionStatus::APPROVED,
            PRFMissionSubscriptionStatus::PENDING,
        ]);
    }

    /**
     * @return HasMany<MissionSubscription, $this>
     */
    public function approvedSubscriptions(): HasMany
    {
        return $this->missionSubscriptions()->where('status', PRFMissionSubscriptionStatus::APPROVED);
    }

    /**
     * @return HasMany<Soul, $this>
     */
    public function souls(): HasMany
    {
        return $this->hasMany(Soul::class);
    }

    /**
     * @return HasMany<DebriefNote, $this>
     */
    public function debriefNotes(): HasMany
    {
        return $this->hasMany(DebriefNote::class);
    }

    /**
     * @return HasMany<CohortMission, $this>
     */
    public function cohortMissions(): HasMany
    {
        return $this->hasMany(CohortMission::class);
    }

    /**
     * @return HasMany<MissionQuestion, $this>
     */
    public function missionQuestions(): HasMany
    {
        return $this->hasMany(MissionQuestion::class);
    }

    public function weatherForecasts(): MorphMany
    {
        return $this->morphMany(related: WeatherForecast::class, name: 'weather_forecastable');
    }

    public function smsLogs(): MorphMany
    {
        return $this->morphMany(related: SMSLog::class, name: 'sms_loggable');
    }

    /**
     * @return HasOne<MissionSubscription, $this>
     */
    public function loggedInMemberMissionSubscription(): HasOne
    {
        return $this->hasOne(MissionSubscription::class)->where([
            'member_id' => Member::currentMemberIdQuery(),
        ]);
    }

    /**
     * Seats taken, counting offline members. Uses the counts loaded with every mission
     * query ($withCount) and only queries when they are missing.
     */
    public function getMissionSubscriptionsCountAttribute(): int
    {
        return (
            (int) ($this->active_subscriptions_count ?? $this->activeSubscriptions()->count())
            + (int) ($this->offline_members_count ?? $this->offlineMembers()->count())
        );
    }

    public function getMissionSubscriptionsNeededAttribute(): int
    {
        return (
            (int) $this->capacity
            - (int) ($this->approved_subscriptions_count ?? $this->approvedSubscriptions()->count())
            - (int) ($this->offline_members_count ?? $this->offlineMembers()->count())
        );
    }

    public function getLocationAttribute()
    {
        $school = $this->school;

        return "{$school->latitude},{$school->longitude}";
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MISSION_PHOTOS)->acceptsMimeTypes([
            'image/jpg',
            'image/jpeg',
            'image/tiff',
            'image/png',
            'image/heic',
            'image/heif',
        ]);

        $this->addMediaCollection(self::MISSION_VIDEOS)->acceptsMimeTypes([
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

    /**
     * @return HasMany<MissionSession, $this>
     */
    public function missionSessions(): HasMany
    {
        return $this->hasMany(MissionSession::class);
    }

    public function offlineMembers(): HasMany
    {
        return $this->hasMany(MissionOfflineMember::class);
    }

    protected function startTime(): Attribute
    {
        return Attribute::get(fn($value) => $value ? \Carbon\Carbon::parse($value)->format('H:i') : null);
    }

    protected function endTime(): Attribute
    {
        return Attribute::get(fn($value) => $value ? \Carbon\Carbon::parse($value)->format('H:i') : null);
    }

    protected function statusLabel(): Attribute
    {
        return Attribute::get(fn() => $this->status->getLabel());
    }

    public function missionPhotos(): MorphMany
    {
        return $this->media()->where('collection_name', self::MISSION_PHOTOS);
    }

    public function missionVideos(): MorphMany
    {
        return $this->media()->where('collection_name', self::MISSION_VIDEOS);
    }

    /**
     * @return MorphOne<AccountingEvent, $this>
     */
    public function accountingEvent(): MorphOne
    {
        return $this->morphOne(related: AccountingEvent::class, name: 'accounting_eventable');
    }

    /**
     * @return HasManyThrough<Requisition, $this>
     */
    public function requisitions(): HasManyThrough
    {
        return $this->hasManyThrough(
            related: Requisition::class,
            through: AccountingEvent::class,
            firstKey: 'accounting_eventable_id',
            secondKey: 'accounting_event_id',
        )->where('accounting_eventable_type', PRFMorphType::MISSION);
    }

    public function scopeFellowshipFunded($query)
    {
        return $query->whereHas('requisitions', function ($requisitionQuery) {
            $requisitionQuery->where('total_amount', '>', 0);
        });
    }

    public function scopeMemberFunded($query)
    {
        return $query->whereHas('requisitions', function ($requisitionQuery) {
            $requisitionQuery->where('total_amount', 0);
        });
    }

    /**
     * Scope to find missions that conflict with the given mission.
     * A conflict requires overlapping date ranges, overlapping time ranges,
     * and the mission must have a subscribable status.
     */
    public function scopeConflictingWith($query, Mission $mission): void
    {
        $query
            ->where('missions.id', '!=', $mission->id)
            ->whereIn('status', PRFMissionStatus::subscribable())
            ->where(function ($q) use ($mission) {
                $q->where(function ($q) use ($mission) {
                    $q->whereDate('start_date', '>=', $mission->start_date)->whereDate(
                        'start_date',
                        '<=',
                        $mission->end_date,
                    );
                })->orWhere(function ($q) use ($mission) {
                    $q->whereDate('end_date', '>=', $mission->start_date)->whereDate(
                        'end_date',
                        '<=',
                        $mission->end_date,
                    );
                })->orWhere(function ($q) use ($mission) {
                    $q->whereDate('start_date', '<=', $mission->start_date)->whereDate(
                        'end_date',
                        '>=',
                        $mission->end_date,
                    );
                });
            })
            ->whereTime('start_time', '<', $mission->end_time)
            ->whereTime('end_time', '>', $mission->start_time);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('end_date', '>=', now());
    }

    public function scopePast($query)
    {
        return $query->where('end_date', '<', now());
    }
}
