<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Enums\PRFActiveStatus;
use App\Enums\PRFLessonType;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasULID;
use Database\Factories\LessonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

#[Fillable([
    'name',
    'slug',
    'description',
    'type',
    'content',
    'video_url',
    'audio_url',
    'document_url',
    'is_active',
])]
class Lesson extends Model implements HasMedia, HasQueryBuilderCapabilities
{
    use BelongsToTenant;
    /** @use HasFactory<LessonFactory> */
    use HasFactory;
    use HasModelPermissions;
    use HasSlug;
    use HasULID;
    use InteractsWithMedia;
    use LogsActivity;
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'type' => PRFLessonType::class,
            'is_active' => PRFActiveStatus::class,
        ];
    }

    public const INCLUDES = ['lessonModules', 'thumbnail'];

    public const SORTS = ['created_at', 'updated_at'];

    public static function filters(): array
    {
        return [];
    }

    public const THUMBNAILS = 'thumbnails';

    public const VIDEO = 'videos';

    public const AUDIO = 'audios';

    public const DOCUMENT = 'documents';

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()->generateSlugsFrom('name')->saveSlugsTo('slug');
    }

    /**
     * @return HasMany<LessonModule, $this>
     */
    public function lessonModules(): HasMany
    {
        return $this->hasMany(related: LessonModule::class);
    }

    /**
     * @return HasMany<LessonMember, $this>
     */
    public function lessonMembers(): HasMany
    {
        return $this->hasMany(related: LessonMember::class);
    }

    /**
     * @return MorphMany<Media, $this>
     */
    public function videos(): MorphMany
    {
        return $this->media()->where('collection_name', self::VIDEO);
    }

    /**
     * @return MorphMany<Media, $this>
     */
    public function audios(): MorphMany
    {
        return $this->media()->where('collection_name', self::AUDIO);
    }

    /**
     * @return MorphMany<Media, $this>
     */
    public function documents(): MorphMany
    {
        return $this->media()->where('collection_name', self::DOCUMENT);
    }

    /**
     * @return HasOne<Media, $this>
     */
    public function thumbnail(): HasOne
    {
        return $this->hasOne(related: Media::class, foreignKey: 'model_id')->where([
            'collection_name' => self::THUMBNAILS,
            'model_type' => self::class,
        ]);
    }

    /**
     * @return HasOne<LessonMember, $this>
     */
    public function lessonMember(): HasOne
    {
        return $this->hasOne(LessonMember::class)->where([
            'member_id' => Member::currentMemberIdQuery(),
        ]);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
