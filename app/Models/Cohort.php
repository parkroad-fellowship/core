<?php

namespace App\Models;

use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Cohort extends Model
{
    use HasFactory;
    use HasSlug;
    use SoftDeletes;
    use HasUlid;

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
}
