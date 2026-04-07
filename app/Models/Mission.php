<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Enums\PRFMissionStatus;
use App\Enums\PRFMissionSubscriptionStatus;
use App\Enums\PRFMorphType;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasUlid;
use App\Observers\MissionObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\QueryBuilder\AllowedFilter;

#[ObservedBy(MissionObserver::class)]
class Mission extends Model implements HasMedia, HasQueryBuilderCapabilities
{
    use HasFactory;
    use HasModelPermissions;
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
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'status' => 'integer',
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
                $query->where(
                    'school_term_id',
                    SchoolTerm::query()
                        ->select('id')
                        ->where('ulid', $value)
                        ->limit(1)
                );
            }),
            AllowedFilter::callback('mission_type_ulid', function ($query, $value) {
                $query->where(
                    'mission_type_id',
                    MissionType::query()
                        ->select('id')
                        ->where('ulid', $value)
                        ->limit(1)
                );
            }),
            AllowedFilter::callback('school_ulid', function ($query, $value) {
                $query->where(
                    'school_id',
                    School::query()
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
            AllowedFilter::callback('unsubscribed', function ($query) {
                $query->whereDoesntHave('missionSubscriptions', function ($query) {
                    $query->where('member_id', Member::query()
                        ->where('user_id', Auth::id())
                        ->limit(1)
                        ->select('id'));
                });
            }),
            AllowedFilter::scope('upcoming'),
            AllowedFilter::scope('past'),
            AllowedFilter::callback('search', function ($query, $value) {
                $query->where(function ($query) use ($value) {
                    $query->whereLike('theme', "%{$value}%")
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
            ->count() + $this->offlineMembers->count();
    }

    public function getMissionSubscriptionsNeededAttribute()
    {
        return $this->capacity - ($this->missionSubscriptions()
            ->whereIn('status', [PRFMissionSubscriptionStatus::APPROVED])
            ->count() + $this->offlineMembers->count());
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

    public function offlineMembers(): HasMany
    {
        return $this->hasMany(MissionOfflineMember::class);
    }

    protected function startTime(): Attribute
    {
        return Attribute::get(fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('H:i') : null);
    }

    protected function endTime(): Attribute
    {
        return Attribute::get(fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('H:i') : null);
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

    public function scopeFellowshipFunded($query)
    {
        return $query
            ->whereHas('requisitions', function ($requisitionQuery) {
                $requisitionQuery->where('total_amount', '>', 0);
            });
    }

    public function scopeMemberFunded($query)
    {
        return $query
            ->whereHas('requisitions', function ($requisitionQuery) {
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
                    $q->whereDate('start_date', '>=', $mission->start_date)
                        ->whereDate('start_date', '<=', $mission->end_date);
                })
                    ->orWhere(function ($q) use ($mission) {
                        $q->whereDate('end_date', '>=', $mission->start_date)
                            ->whereDate('end_date', '<=', $mission->end_date);
                    })
                    ->orWhere(function ($q) use ($mission) {
                        $q->whereDate('start_date', '<=', $mission->start_date)
                            ->whereDate('end_date', '>=', $mission->end_date);
                    });
            })
            ->whereTime('start_time', '<', $mission->end_time)
            ->whereTime('end_time', '>', $mission->start_time);
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
