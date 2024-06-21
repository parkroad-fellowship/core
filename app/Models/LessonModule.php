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
}
