<?php

namespace App\Models;

use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Module extends Model implements HasMedia
{
    use HasFactory;
    use HasSlug;
    use HasUlid;
    use InteractsWithMedia;
    use LogsActivity;
    use SoftDeletes;

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

    public function courseModules()
    {
        return $this->hasMany(
            related: CourseModule::class,
        );
    }

    public function lessonModules()
    {
        return $this->hasMany(
            related: LessonModule::class,
        );
    }

    public function lessonMembers()
    {
        return $this->hasMany(
            related: LessonMember::class,
        );
    }

    public function thumbnail()
    {
        return $this->hasOne(
            related: Media::class,
            foreignKey: 'model_id',

        )->where([
            'collection_name' => self::THUMBNAILS,
            'model_type' => self::class,
        ]);
    }

    public function memberModule()
    {
        return $this
            ->hasOne(MemberModule::class)
            ->where([
                'member_id' => Member::query()
                    ->where('user_id', Auth::id())
                    ->limit(1)
                    ->select('id'),
            ]);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
