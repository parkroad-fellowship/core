<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Enums\PRFActiveStatus;
use App\Enums\PRFEventType;
use App\Enums\PRFResponsibleDesk;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasULID;
use App\Observers\PRFEventObserver;
use App\Policies\EventPolicy;
use Database\Factories\PRFEventFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\QueryBuilder\AllowedFilter;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

#[Fillable([
    'ulid',
    'name',
    'description',
    'start_date',
    'start_time',
    'end_date',
    'end_time',
    'venue',
    'latitude',
    'longitude',
    'location',
    'capacity',
    'status',
    'dressing_recommendations',
    'weather_recommendations',
    'responsible_desk',
    'event_type',
])]
#[Appends([
    'location',
    'event_subscriptions_needed',
])]
#[Table('prf_events')]
#[ObservedBy(PRFEventObserver::class)]
#[UsePolicy(EventPolicy::class)]
class PRFEvent extends Model implements HasMedia, HasQueryBuilderCapabilities
{
    use BelongsToTenant;
    /** @use HasFactory<PRFEventFactory> */
    use HasFactory;

    use HasModelPermissions;
    use HasULID;
    use InteractsWithMedia;
    use SoftDeletes;

    public static function permissionEntity(): string
    {
        return 'event';
    }

    public const MEDIA_COLLECTIONS = [
        self::EVENT_PHOTOS,
        self::EVENT_POSTERS,
        self::EVENT_RECORDINGS,
    ];

    public const EVENT_PHOTOS = 'event-photos';

    public const EVENT_POSTERS = 'event-posters';

    public const EVENT_RECORDINGS = 'event-recordings';

    public const INCLUDES = [
        'posters',
        'media',
        'transcripts',
        'transcripts.media',
        'eventSubscriptions',
        'weatherForecasts',
        'loggedInMemberEventSubscription',
        'eventHandlers',
        'accountingEvent',
        'participants',
        'participants.member',
        'requisitions',
    ];

    public const SORTS = ['created_at', 'updated_at', 'start_date'];

    /**
     * @return array<int, AllowedFilter>
     */
    public static function filters(): array
    {
        return [
            AllowedFilter::callback('status_key', function ($query, $value) {
                $query->where('status', $value);
            }),
            AllowedFilter::callback('status_keys', function ($query, $value) {
                $query->whereIn('status', Arr::wrap($value));
            }),
            AllowedFilter::callback('unsubscribed', function ($query) {
                $query->whereDoesntHave('eventSubscriptions', function ($query) {
                    $query->where('member_id', Member::currentMemberIdQuery());
                });
            }),
            AllowedFilter::exact('event_type'),
            AllowedFilter::exact('responsible_desk'),
            AllowedFilter::callback('responsible_desks', function ($query, $value) {
                $query->whereIn('responsible_desk', Arr::wrap($value));
            }),
            AllowedFilter::scope('upcoming'),
            AllowedFilter::scope('past'),
            AllowedFilter::callback('is_camp_committee_member', function ($query, $value) {
                $query->whereHas('participants', function ($query) {
                    $query->where('member_id', Member::currentMemberIdQuery());
                });
            }),
        ];
    }

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => PRFActiveStatus::class,
            'latitude' => 'double',
            'longitude' => 'double',
            'event_type' => PRFEventType::class,
            'responsible_desk' => PRFResponsibleDesk::class,
        ];
    }

    /**
     * Returns the 'latitude' and 'longitude' attributes as the computed 'location' attribute,
     * as a standard Google Maps style Point array with 'lat' and 'lng' attributes.
     *
     * Used by the Filament Google Maps package.
     *
     * Requires the 'location' attribute be included in this model's $fillable array.
     */
    public function getLocationAttribute(): array
    {
        return [
            'lat' => (float) $this->latitude,
            'lng' => (float) $this->longitude,
        ];
    }

    /**
     * Takes a Google style Point array of 'lat' and 'lng' values and assigns them to the
     * 'latitude' and 'longitude' attributes on this model.
     *
     * Used by the Filament Google Maps package.
     *
     * Requires the 'location' attribute be included in this model's $fillable array.
     */
    public function setLocationAttribute(?array $location): void
    {
        if (is_array($location)) {
            $this->attributes['latitude'] = $location['lat'];
            $this->attributes['longitude'] = $location['lng'];
            unset($this->attributes['location']);
        }
    }

    /**
     * Get the lat and lng attribute/field names used on this table
     *
     * Used by the Filament Google Maps package.
     *
     * @return string[]
     */
    public static function getLatLngAttributes(): array
    {
        return [
            'lat' => 'latitude',
            'lng' => 'longitude',
        ];
    }

    /**
     * Get the name of the computed location attribute
     *
     * Used by the Filament Google Maps package.
     */
    public static function getComputedLocation(): string
    {
        return 'location';
    }

    /**
     * @return HasMany<EventSubscription, $this>
     */
    public function eventSubscriptions(): HasMany
    {
        return $this->hasMany(related: EventSubscription::class, foreignKey: 'prf_event_id');
    }

    public function weatherForecasts(): MorphMany
    {
        return $this->morphMany(related: WeatherForecast::class, name: 'weather_forecastable');
    }

    /**
     * @return MorphMany<Media, $this>
     */
    public function posters(): MorphMany
    {
        return $this->media()->where('collection_name', self::EVENT_POSTERS);
    }

    /**
     * @return MorphMany<Media, $this>
     */
    public function photos(): MorphMany
    {
        return $this->media()->where('collection_name', self::EVENT_PHOTOS);
    }

    /**
     * @return HasOne<EventSubscription, $this>
     */
    public function loggedInMemberEventSubscription(): HasOne
    {
        return $this->hasOne(related: EventSubscription::class, foreignKey: 'prf_event_id')->where([
            'member_id' => Member::currentMemberIdQuery(),
        ]);
    }

    public function getEventSubscriptionsNeededAttribute()
    {
        if ($this->capacity === 0) {
            return null;
        }

        return $this->capacity - $this->eventSubscriptions()->count();
    }

    /**
     * @return HasMany<PRFEventHandler, $this>
     */
    public function eventHandlers(): HasMany
    {
        return $this->hasMany(related: PRFEventHandler::class, foreignKey: 'prf_event_id');
    }

    /**
     * @return MorphOne<AccountingEvent, $this>
     */
    public function accountingEvent(): MorphOne
    {
        return $this->morphOne(related: AccountingEvent::class, name: 'accounting_eventable');
    }

    public function transcripts(): MorphMany
    {
        return $this->morphMany(related: Transcript::class, name: 'transcriptable');
    }

    public function requisitions()
    {
        return $this->morphMany(related: Requisition::class, name: 'requisitionable');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('end_date', '>=', now());
    }

    public function scopePast($query)
    {
        return $query->where('end_date', '<', now());
    }

    /**
     * @return HasMany<PRFEventParticipant, $this>
     */
    public function participants(): HasMany
    {
        return $this->hasMany(PRFEventParticipant::class, 'prf_event_id');
    }
}
