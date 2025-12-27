<?php

namespace App\Models;

use App\Observers\SchoolObserver;
use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

#[ObservedBy(SchoolObserver::class)]
class School extends Model
{
    use HasFactory;
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
    ];

    protected $appends = [
        'location',
    ];

    protected $casts = [
        'latitude' => 'double',
        'longitude' => 'double',
    ];

    const INCLUDES = [
        'schoolContacts',
        'schoolContacts.contactType',
        'schoolContacts.school',
        'missions',
    ];

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
}
