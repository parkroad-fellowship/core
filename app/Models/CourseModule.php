<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasULID;
use Database\Factories\CourseModuleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\QueryBuilder\AllowedFilter;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

/**
 * @property int $order
 * @property-read Course $course
 * @property-read Module $module
 */
#[Fillable([
    'ulid',
    'course_id',
    'module_id',
    'order',
])]
class CourseModule extends Model implements HasQueryBuilderCapabilities
{
    use BelongsToTenant;
    /** @use HasFactory<CourseModuleFactory> */
    use HasFactory;
    use HasModelPermissions;
    use HasULID;
    use LogsActivity;
    use SoftDeletes;

    public const INCLUDES = [
        'course',
        'course.thumbnail',
        'course.courseMember',
        'module',
        'module.thumbnail',
        'memberModule',
        'module.lessonModules',
        'module.lessonModules.lesson',
        'module.lessonModules.lessonMember',
        'module.lessonModules.module',
    ];

    public const SORTS = ['created_at', 'updated_at', 'order'];

    protected function casts(): array
    {
        return [
            'order' => 'integer',
        ];
    }

    /**
     * @return array<int, string|AllowedFilter>
     */
    public static function filters(): array
    {
        return [
            AllowedFilter::exact('ulid'),
            AllowedFilter::callback('course_ulid', function ($query, $value) {
                $query->where('course_id', Course::query()->select('id')->where('ulid', $value)->limit(1));
            }),
            AllowedFilter::callback('module_ulid', function ($query, $value) {
                $query->where('module_id', Module::query()->select('id')->where('ulid', $value)->limit(1));
            }),
        ];
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(related: Course::class);
    }

    /**
     * @return BelongsTo<Module, $this>
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(related: Module::class);
    }

    /**
     * @return HasOne<MemberModule, $this>
     */
    public function memberModule(): HasOne
    {
        return $this->hasOne(related: MemberModule::class, foreignKey: 'module_id', localKey: 'module_id')->where([
            'member_id' => Member::currentMemberIdQuery(),
        ]);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
