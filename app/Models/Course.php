<?php

namespace App\Models;

use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Course extends Model implements HasMedia
{
    use HasFactory;
    use HasUlid;
    use SoftDeletes;
    use HasSlug;
    use InteractsWithMedia;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
    ];

    const THUMBNAILS = 'thumbnails';

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }
}
