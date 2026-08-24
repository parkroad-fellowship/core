<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Enums\PRFActiveStatus;
use App\Enums\PRFInstitutionType;
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
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

#[ObservedBy(SchoolObserver::class)]
class School extends Model implements HasQueryBuilderCapabilities
{
    use BelongsToTenant;
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

    protected function casts(): array
    {
        return [
            'latitude' => 'double',
            'longitude' => 'double',
            'mission_defaults' => 'array',
            'is_active' => PRFActiveStatus::class,
            'institution_type' => PRFInstitutionType::class,
        ];
    }

    public const INCLUDES = [
        'schoolContacts',
        'schoolContacts.contactType',
        'schoolContacts.school',
        'budgetEstimates',
        'budgetEstimates.budgetEstimateEntries',
        'budgetEstimates.budgetEstimateEntries.expenseCategory',
        'missions.schoolTerm',
        'missions.missionType',
        'missions.school.schoolContacts',
        'missions.school.schoolContacts.contactType',
        'missions.missionSubscriptions',
        'missions.missionSubscriptions.member',
        'missions.souls',
        'missions.loggedInMemberMissionSubscription',
        'missions.weatherForecasts',
        'missions.media',
        'missions.missionQuestions',
        'missions.missionSessions',
        'missions.accountingEvent',
        'missions.accountingEvent.allocationEntries',
        'missions.accountingEvent.refunds',
        'missions.accountingEvent.latestRefund',
        'missions.school.budgetEstimates',
        'missions.school.budgetEstimates.budgetEstimateEntries',
        'missions.school.budgetEstimates.budgetEstimateEntries.expenseCategory',
        'missions.requisitions',
        'missions.requisitions.requisitionItems',
        'missions.requisitions.requisitionItems.expenseCategory',
        'missions.offlineMembers',
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
     * Get mission defaults for a specific mission type from saved settings,
     * falling back to the most recent SERVICED mission of that type.
     *
     * @return array{
     *     default_start_time: string|null,
     *     default_end_time: string|null,
     *     default_capacity: int|null,
     *     default_mission_type_id: int|null,
     *     source: string
     * }
     */
    public function getMissionDefaults(?int $missionTypeId = null): array
    {
        $defaults = [
            'default_start_time' => null,
            'default_end_time' => null,
            'default_capacity' => null,
            'default_mission_type_id' => null,
            'source' => 'none',
        ];

        // Resolve which type we need defaults for
        if ($missionTypeId === null) {
            $missionTypeId = $this->mission_defaults['default_mission_type_id'] ?? null;
        }

        // 1. Saved per-type defaults
        if ($missionTypeId !== null) {
            $typeDefaults = $this->mission_defaults['types'][(string) $missionTypeId] ?? null;

            if (is_array($typeDefaults) && filled($typeDefaults)) {
                return [
                    'default_start_time' => $typeDefaults['start_time'] ?? null,
                    'default_end_time' => $typeDefaults['end_time'] ?? null,
                    'default_capacity' => isset($typeDefaults['capacity']) ? (int) $typeDefaults['capacity'] : null,
                    'default_mission_type_id' => (int) $missionTypeId,
                    'source' => 'school_defaults',
                ];
            }
        }

        // 2. Most recent serviced mission of that type
        $recentMission = $this->missions()
            ->when($missionTypeId !== null, fn ($query) => $query->where('mission_type_id', $missionTypeId))
            ->where('status', PRFMissionStatus::SERVICED)
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

        // 3. Legacy flat keys (pre-migration data)
        $savedDefaults = $this->mission_defaults;
        if (
            is_array($savedDefaults)
            && ! array_key_exists('types', $savedDefaults)
            && filled($savedDefaults['default_start_time'] ?? null)
        ) {
            return [
                'default_start_time' => $savedDefaults['default_start_time'],
                'default_end_time' => $savedDefaults['default_end_time'] ?? null,
                'default_capacity' => isset($savedDefaults['default_capacity']) ? (int) $savedDefaults['default_capacity'] : null,
                'default_mission_type_id' => $savedDefaults['default_mission_type_id'] ?? null,
                'source' => 'school_defaults',
            ];
        }

        return $defaults;
    }

    /**
     * Upsert per-mission-type defaults into the mission_defaults JSON.
     *
     * @param  array<int, array{mission_type_id: int, start_time?: string|null, end_time?: string|null, capacity?: int|null}>  $entries
     */
    public function setMissionTypeDefaults(array $entries, ?int $defaultMissionTypeId = null): void
    {
        $defaults = is_array($this->mission_defaults) ? $this->mission_defaults : [];
        $types = $defaults['types'] ?? [];

        foreach ($entries as $entry) {
            $key = (string) $entry['mission_type_id'];

            $types[$key] = array_filter([
                'start_time' => $entry['start_time'] ?? null,
                'end_time' => $entry['end_time'] ?? null,
                'capacity' => isset($entry['capacity']) ? (int) $entry['capacity'] : null,
            ], fn ($value) => filled($value));

            if (empty($types[$key])) {
                unset($types[$key]);
            }
        }

        $payload = ['types' => $types];

        $resolvedDefault = $defaultMissionTypeId ?? $defaults['default_mission_type_id'] ?? null;
        if ($resolvedDefault !== null) {
            $payload['default_mission_type_id'] = (int) $resolvedDefault;
        }

        $this->update(['mission_defaults' => $payload]);
    }

    /**
     * Remove the saved defaults for a single mission type.
     */
    public function forgetMissionTypeDefault(int $missionTypeId): void
    {
        $defaults = is_array($this->mission_defaults) ? $this->mission_defaults : [];

        if (! isset($defaults['types'][(string) $missionTypeId])) {
            return;
        }

        unset($defaults['types'][(string) $missionTypeId]);

        $this->update(['mission_defaults' => $defaults]);
    }

    /**
     * Load the mission types referenced in the mission_defaults JSON in one query.
     *
     * @return \Illuminate\Support\Collection<int, MissionType>
     */
    public function getMissionDefaultTypes(): \Illuminate\Support\Collection
    {
        $missionTypeIds = collect($this->mission_defaults['types'] ?? [])
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($defaultId = $this->mission_defaults['default_mission_type_id'] ?? null) {
            $missionTypeIds->push((int) $defaultId);
        }

        return MissionType::query()
            ->whereIn('id', $missionTypeIds->all())
            ->get()
            ->keyBy('id');
    }

    /**
     * The ACTIVE budget estimate to baseline a mission on: an exact match for
     * the given mission type first, otherwise the school's estimate for the
     * fallback mission type (Sunday Service).
     */
    public function getBudgetEstimateFor(int $missionTypeId): ?BudgetEstimate
    {
        return BudgetEstimate::forSchoolAndType($this->id, $missionTypeId);
    }
}
