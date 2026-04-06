<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Enums\PRFMissionStatus;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasUlid;
use App\Observers\SchoolObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\QueryBuilder\AllowedFilter;

#[ObservedBy(SchoolObserver::class)]
class School extends Model implements HasQueryBuilderCapabilities
{
    use HasFactory;
    use HasModelPermissions;
    use HasUlid;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'total_students',
        'address',
        'directions',
        'latitude',
        'longitude',
        'is_active',
        'location',
        'distance',
        'static_duration',
        'institution_type',
        'mission_defaults',
    ];

    protected $appends = [
        'location',
    ];

    protected $casts = [
        'latitude' => 'double',
        'longitude' => 'double',
        'mission_defaults' => 'array',
    ];

    public const INCLUDES = [
        'schoolContacts',
        'schoolContacts.contactType',
        'schoolContacts.school',
        'missions',
        'budgetEstimates',
        'budgetEstimates.budgetEstimateEntries',
        'budgetEstimates.budgetEstimateEntries.expenseCategory',
    ];

    public const SORTS = ['created_at', 'updated_at', 'name'];

    public static function filters(): array
    {
        return [
            AllowedFilter::callback('search', function ($query, $value) {
                $query->where(function ($query) use ($value) {
                    $query->where('name', 'ILIKE', "%{$value}%")
                        ->orWhere('description', 'ILIKE', "%{$value}%");
                });
            }),
        ];
    }

    public function schoolContacts()
    {
        return $this->hasMany(SchoolContact::class);
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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

    public function missions()
    {
        return $this->hasMany(Mission::class);
    }

    public function budgetEstimates()
    {
        return $this->morphMany(
            related: BudgetEstimate::class,
            name: 'budget_estimatable',
        );
    }

    /**
     * Get mission defaults from saved settings or fallback to the most recent SERVICED mission.
     *
     * @return array{
     *     default_start_time: string|null,
     *     default_end_time: string|null,
     *     default_capacity: int|null,
     *     default_mission_type_id: int|null,
     *     source: string
     * }
     */
    public function getMissionDefaults(): array
    {
        $defaults = [
            'default_start_time' => null,
            'default_end_time' => null,
            'default_capacity' => null,
            'default_mission_type_id' => null,
            'source' => 'none',
        ];

        $savedDefaults = $this->mission_defaults;
        if ($savedDefaults && is_array($savedDefaults)) {
            $hasAnyDefault = ! empty($savedDefaults['default_start_time'])
                || ! empty($savedDefaults['default_end_time'])
                || ! empty($savedDefaults['default_capacity'])
                || ! empty($savedDefaults['default_mission_type_id']);

            if ($hasAnyDefault) {
                return [
                    'default_start_time' => $savedDefaults['default_start_time'] ?? null,
                    'default_end_time' => $savedDefaults['default_end_time'] ?? null,
                    'default_capacity' => $savedDefaults['default_capacity'] ?? null,
                    'default_mission_type_id' => $savedDefaults['default_mission_type_id'] ?? null,
                    'source' => 'school_defaults',
                ];
            }
        }

        $recentMission = $this->missions()
            ->where('status', PRFMissionStatus::SERVICED->value)
            ->latest('end_date')
            ->first();

        if ($recentMission) {
            return [
                'default_start_time' => $recentMission->start_time,
                'default_end_time' => $recentMission->end_time,
                'default_capacity' => $recentMission->capacity,
                'default_mission_type_id' => $recentMission->mission_type_id,
                'source' => 'recent_mission',
            ];
        }

        return $defaults;
    }
}
