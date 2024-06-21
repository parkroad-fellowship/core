<?php

namespace App\Models;

use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MemberModule extends Model
{
    use HasFactory;
    use HasUlid;
    use SoftDeletes;

    protected $fillable = [
        'course_id',
        'module_id',
        'member_id',
        'percent_complete',
        'completion_status',
        'completed_at',
    ];

    protected $casts = [
        'percent_complete' => 'float',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
