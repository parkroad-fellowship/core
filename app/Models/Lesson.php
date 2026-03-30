<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Lesson extends Model implements HasMedia, HasQueryBuilderCapabilities
{
    use HasFactory;
    use HasModelPermissions;
    use HasSlug;
    use HasUlid;
    use InteractsWithMedia;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'type',
        'content',
        'video_url',
        'audio_url',
        'document_url',
        'is_active',
    ];

    public const INCLUDES = ['lessonModules', 'thumbnail'];

    public const SORTS = ['created_at', 'updated_at'];

    public static function filters(): array
    {
        return [];
    }

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

    public function lessonMembers()
    {
        return $this->hasMany(
            related: LessonMember::class,
        );
    }

    public function videos()
    {
        return $this
            ->media()
            ->where('collection_name', self::VIDEO);
    }

    public function audios()
    {
        return $this
            ->media()
            ->where('collection_name', self::AUDIO);
    }

    public function documents()
    {
        return $this
            ->media()
            ->where('collection_name', self::DOCUMENT);
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

    public function lessonMember()
    {
        return $this
            ->hasOne(LessonMember::class)
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
