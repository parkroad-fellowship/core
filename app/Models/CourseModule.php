<?php

namespace App\Models;

use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CourseModule extends Model
{
    use HasFactory;
    use HasUlid;
    use SoftDeletes;

    protected $fillable = [
        'ulid',
        'course_id',
        'module_id',
        'order',
    ];

    const INCLUDES = [
        'course',
        'course.thumbnail',
        'course.courseMember',
        'module',
        'module.thumbnail',
        'module.memberModule',
        'module.lessonModules',
        'module.lessonModules.lesson',
        'module.lessonModules.lessonMember',
        'module.lessonModules.module',
    ];

    public function course()
    {
        return $this->belongsTo(
            related: Course::class,
        );
    }

    public function module()
    {
        return $this->belongsTo(
            related: Module::class,
        );
    }
}
