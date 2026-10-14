<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Enums\PRFActiveStatus;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasULID;
use Database\Factories\ModuleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

/**
 * @property PRFActiveStatus $is_active
 * @property-read \Illuminate\Database\Eloquent\Collection<int, LessonModule> $lessonModules
 * @property-read \Illuminate\Database\Eloquent\Collection<int, CourseModule> $courseModules
 */
#[Fillable([
    'name',
    'slug',
    'description',
    'is_active',
])]
class Module extends Model implements HasMedia, HasQueryBuilderCapabilities
{
    use BelongsToTenant;
    /** @use HasFactory<ModuleFactory> */
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
            'is_active' => PRFActiveStatus::class,
        ];
    }

    public const INCLUDES = ['courseModules', 'lessonModules', 'thumbnail'];

    public const SORTS = ['created_at', 'updated_at'];

    public static function filters(): array
    {
        return [];
    }

    public const THUMBNAILS = 'thumbnails';

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::THUMBNAILS)->singleFile()->acceptsMimeTypes(Lesson::IMAGE_TYPES);
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()->generateSlugsFrom('name')->saveSlugsTo('slug');
    }

    /**
     * @return HasMany<CourseModule, $this>
     */
    public function courseModules(): HasMany
    {
        return $this->hasMany(related: CourseModule::class);
    }

    /**
     * @return HasMany<LessonModule, $this>
     */
    public function lessonModules(): HasMany
    {
        return $this->hasMany(related: LessonModule::class);
    }

    /**
     * @return HasMany<MemberModule, $this>
     */
    public function memberModules(): HasMany
    {
        return $this->hasMany(related: MemberModule::class);
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
     * @return HasOne<MemberModule, $this>
     */
    public function memberModule(): HasOne
    {
        return $this->hasOne(MemberModule::class)->where([
            'member_id' => Member::currentMemberIdQuery(),
        ]);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
