<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Cohort extends Model implements HasQueryBuilderCapabilities
{
    use HasFactory;
    use HasModelPermissions;
    use HasSlug;
    use HasUlid;
    use LogsActivity;
    use SoftDeletes;

    public const INCLUDES = [
        'cohortMissions',
        'cohortLetters',
    ];

    public const SORTS = ['created_at', 'updated_at'];

    protected $fillable = [
        'ulid',
        'title',
        'slug',
        'start_date',
        'is_active',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug');
    }

    public function cohortMissions()
    {
        return $this->hasMany(CohortMission::class);
    }

    public function cohortLetters()
    {
        return $this->hasMany(CohortLetter::class);
    }

    public static function filters(): array
    {
        return [];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
