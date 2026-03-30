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
use Spatie\QueryBuilder\AllowedFilter;

class LessonModule extends Model implements HasQueryBuilderCapabilities
{
    use HasFactory;
    use HasModelPermissions;
    use HasUlid;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'lesson_id',
        'module_id',
        'order',
    ];

    const INCLUDES = [
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
                $query->where(
                    'lesson_id',
                    Lesson::query()
                        ->select('id')
                        ->where('ulid', $value)
                        ->limit(1)
                );
            }),
            AllowedFilter::callback('module_ulid', function ($query, $value) {
                $query->where(
                    'module_id',
                    Module::query()
                        ->select('id')
                        ->where('ulid', $value)
                        ->limit(1)
                );
            }),
        ];
    }

    public function lesson()
    {
        return $this->belongsTo(
            related: Lesson::class,
        );
    }

    public function module()
    {
        return $this->belongsTo(
            related: Module::class,
        );
    }

    public function lessonMember()
    {
        return $this
            ->hasOne(
                related: LessonMember::class,
                foreignKey: 'lesson_id',
                localKey: 'lesson_id',
            )
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
