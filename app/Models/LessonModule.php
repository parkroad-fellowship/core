<?php

namespace App\Models;

use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LessonModule extends Model
{
    use HasFactory;
    use HasUlid;
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
                    ->where('user_id', auth()->id())
                    ->limit(1)
                    ->select('id'),
            ]);
    }
}
