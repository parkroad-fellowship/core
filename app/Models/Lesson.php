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

class Lesson extends Model implements HasMedia
{
    use HasFactory;
    use HasSlug;
    use HasUlid;
    use InteractsWithMedia;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'type',
        'is_active',
    ];

    const THUMBNAILS = 'thumbnails';

    const VIDEO = 'videos';

    const AUDIO = 'audios';

    const DOCUMENT = 'documents';

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    public function lessonModules()
    {
        return $this->hasMany(
            related: LessonModule::class,
        );
    }
}
