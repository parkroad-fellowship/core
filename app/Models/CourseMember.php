<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasUlid;
use App\Observers\CourseMemberObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[ObservedBy(CourseMemberObserver::class)]
class CourseMember extends Model implements HasQueryBuilderCapabilities
{
    use HasFactory;
    use HasModelPermissions;
    use HasUlid;
    use LogsActivity;
    use SoftDeletes;

    public const INCLUDES = ['course', 'member'];

    public const SORTS = ['created_at', 'updated_at'];

    public static function filters(): array
    {
        return [];
    }

    protected $fillable = [
        'course_id',
        'member_id',
        'percent_complete',
        'completion_status',
        'completed_at',
    ];

    protected $casts = [
        'percent_complete' => 'float',
        'completed_at' => 'datetime',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
