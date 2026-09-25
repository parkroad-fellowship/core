<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Enums\PRFActiveStatus;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasULID;
use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

#[Fillable([
    'name',
    'slug',
    'description',
    'is_active',
])]
class Course extends Model implements HasMedia, HasQueryBuilderCapabilities
{
    use BelongsToTenant;
    /** @use HasFactory<CourseFactory> */
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

    public const INCLUDES = [
        'courseModules',
        'lessonMembers',
        'thumbnail',
        'courseMember',
    ];

    public const SORTS = ['created_at', 'updated_at'];

    /**
     * @return array<int, string|AllowedFilter>
     */
    public static function filters(): array
    {
        return [
            AllowedFilter::callback('is_active', function ($query, $value) {
                $query->where('is_active', $value);
            }),
            AllowedFilter::callback('group_ulids', function ($query, $value) {
                return $query->whereHas('courseGroups', function ($query) use ($value) {
                    return $query->whereIn(
                        'group_id',
                        Group::query()->whereIn('ulid', Arr::wrap($value))->select('id'),
                    );
                });
            }),
        ];
    }

    public const THUMBNAILS = 'thumbnails';

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
     * @return HasMany<LessonMember, $this>
     */
    public function lessonMembers(): HasMany
    {
        return $this->hasMany(related: LessonMember::class);
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
     * @return HasOne<CourseMember, $this>
     */
    public function courseMember(): HasOne
    {
        return $this->hasOne(CourseMember::class)->where([
            'member_id' => Member::currentMemberIdQuery(),
        ]);
    }

    /**
     * @return HasMany<CourseMember, $this>
     */
    public function courseMembers(): HasMany
    {
        return $this->hasMany(CourseMember::class);
    }

    /**
     * @return HasMany<CourseGroup, $this>
     */
    public function courseGroups(): HasMany
    {
        return $this->hasMany(CourseGroup::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
