<?php

namespace App\Models;

use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class LessonModule extends Model
{
    use HasFactory;
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
    ];

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
