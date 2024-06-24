<?php

namespace App\Models;

use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CourseGroup extends Model
{
    use HasFactory;
    use HasUlid;
    use SoftDeletes;

    protected $fillable = [
        'group_id',
        'course_id',
        'start_date',
    ];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
