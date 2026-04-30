<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasUlid;
use App\Observers\PRFEventObserver;
use Database\Factories\PRFEventFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\QueryBuilder\AllowedFilter;

#[ObservedBy(PRFEventObserver::class)]
class PRFEvent extends Model implements HasMedia, HasQueryBuilderCapabilities
{
    /** @use HasFactory<PRFEventFactory> */
    use HasFactory;

    use HasModelPermissions;
    use HasUlid;
    use InteractsWithMedia;
    use SoftDeletes;

    public static function permissionEntity(): string
    {
        return 'event';
    }

    public $table = 'prf_events';

    public $fillable = [
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
    ];

    public const MEDIA_COLLECTIONS = [
        self::EVENT_PHOTOS,
        self::EVENT_POSTERS,
    ];

    public const EVENT_PHOTOS = 'event-photos';

    public const EVENT_POSTERS = 'event-posters';

    const INCLUDES = [
        'posters',
        'media',
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
                    $query->where('member_id', Member::query()
                        ->where('user_id', Auth::id())
                        ->limit(1)
                        ->select('id'));
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
                    $query->where(
                        'member_id',
                        Member::query()
                            ->where('user_id', Auth::id())
                            ->limit(1)
                            ->select('id')
                    );
                });
            }),
        ];
    }

    protected $appends = [
        'location',
        'event_subscriptions_needed',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'status' => 'integer',
        'latitude' => 'double',
        'longitude' => 'double',
    ];

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

    public function eventSubscriptions()
    {
        return $this->hasMany(
            related: EventSubscription::class,
            foreignKey: 'prf_event_id',
        );
    }

    public function weatherForecasts(): MorphMany
    {
        return $this->morphMany(
            related: WeatherForecast::class,
            name: 'weather_forecastable',
        );
    }

    public function posters()
    {
        return $this->media()
            ->where('collection_name', self::EVENT_POSTERS);
    }

    public function photos()
    {
        return $this->media()
            ->where('collection_name', self::EVENT_PHOTOS);
    }

    public function loggedInMemberEventSubscription()
    {
        return $this
            ->hasOne(
                related: EventSubscription::class,
                foreignKey: 'prf_event_id',
            )
            ->where([
                'member_id' => Member::query()
                    ->where('user_id', Auth::id())
                    ->limit(1)
                    ->select('id'),
            ]);
    }

    public function getEventSubscriptionsNeededAttribute()
    {
        if ($this->capacity === 0) {
            return null;
        }

        return $this->capacity - $this->eventSubscriptions()->count();
    }

    public function eventHandlers()
    {
        return $this->hasMany(
            related: PRFEventHandler::class,
            foreignKey: 'prf_event_id',
        );
    }

    public function accountingEvent()
    {
        return $this->morphOne(
            related: AccountingEvent::class,
            name: 'accounting_eventable',
        );
    }

    protected function requisitions()
    {
        return $this->morphMany(
            related: Requisition::class,
            name: 'requisitionable',
        );
    }

    public function scopeUpcoming($query)
    {
        return $query->where('end_date', '>=', now());
    }

    public function scopePast($query)
    {
        return $query->where('end_date', '<', now());
    }

    public function participants()
    {
        return $this->hasMany(
            PRFEventParticipant::class,
            'prf_event_id',
        );
    }
}
