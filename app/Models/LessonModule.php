<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasULID;
use Database\Factories\LessonModuleFactory;
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

#[Fillable([
    'lesson_id',
    'module_id',
    'order',
])]
class LessonModule extends Model implements HasQueryBuilderCapabilities
{
    use BelongsToTenant;
    /** @use HasFactory<LessonModuleFactory> */
    use HasFactory;
    use HasModelPermissions;
    use HasULID;
    use LogsActivity;
    use SoftDeletes;

    public const INCLUDES = [
        'lesson',
        'module',
        'module.thumbnail',
        'lessonMember',
    ];

    public const SORTS = ['created_at', 'updated_at'];

    /**
     * @return array<int, string|AllowedFilter>
     */
    public static function filters(): array
    {
        return [
            AllowedFilter::exact('ulid'),
            AllowedFilter::callback('lesson_ulid', function ($query, $value) {
                $query->where('lesson_id', Lesson::query()->select('id')->where('ulid', $value)->limit(1));
            }),
            AllowedFilter::callback('module_ulid', function ($query, $value) {
                $query->where('module_id', Module::query()->select('id')->where('ulid', $value)->limit(1));
            }),
        ];
    }

    /**
     * @return BelongsTo<Lesson, $this>
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(related: Lesson::class);
    }

    /**
     * @return BelongsTo<Module, $this>
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(related: Module::class);
    }

    /**
     * @return HasOne<LessonMember, $this>
     */
    public function lessonMember(): HasOne
    {
        return $this->hasOne(related: LessonMember::class, foreignKey: 'lesson_id', localKey: 'lesson_id')->where([
            'member_id' => Member::currentMemberIdQuery(),
        ]);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
