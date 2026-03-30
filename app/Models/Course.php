<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Course extends Model implements HasMedia, HasQueryBuilderCapabilities
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
        'is_active',
    ];

    const INCLUDES = [
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

                    return $query->whereIn('group_id', Group::query()
                        ->whereIn('ulid', Arr::wrap($value))
                        ->select('id'));
                });
            }),
        ];
    }

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

    public function courseMember()
    {
        return $this
            ->hasOne(CourseMember::class)
            ->where([
                'member_id' => Member::query()
                    ->where('user_id', Auth::id())
                    ->limit(1)
                    ->select('id'),
            ]);
    }

    public function courseMembers()
    {
        return $this->hasMany(CourseMember::class);
    }

    public function courseGroups()
    {
        return $this->hasMany(CourseGroup::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
