<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Enums\PRFMorphType;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasUlid;
use App\Observers\StudentEnquiryObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\QueryBuilder\AllowedFilter;

#[ObservedBy(StudentEnquiryObserver::class)]
class StudentEnquiry extends Model implements HasQueryBuilderCapabilities
{
    use HasFactory;
    use HasModelPermissions;
    use HasUlid;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'ulid',
        'student_id',
        'mission_faq_id',
        'content',
    ];

    const INCLUDES = [
        'student',
        'missionFaq',
        'studentEnquiryReplies',
    ];

    public const SORTS = ['created_at', 'updated_at'];

    /**
     * @return array<int, string|AllowedFilter>
     */
    public static function filters(): array
    {
        return [
            AllowedFilter::callback('student_ulid', function ($query, $value) {
                $query->where(
                    'student_id',
                    Student::query()
                        ->select('id')
                        ->where('ulid', $value)
                        ->limit(1)
                );
            }),
            AllowedFilter::callback('mission_faq_ulid', function ($query, $value) {
                $query->where(
                    'mission_faq_id',
                    MissionFaq::query()
                        ->select('id')
                        ->where('ulid', $value)
                        ->limit(1)
                );
            }),
        ];
    }

    protected $appends = [
        'has_replies',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function missionFaq()
    {
        return $this->belongsTo(MissionFaq::class);
    }

    public function studentEnquiryReplies()
    {
        return $this->hasMany(StudentEnquiryReply::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

    public function getHasRepliesAttribute()
    {
        return $this
            ->studentEnquiryReplies()
            ->where('commentorable_type', PRFMorphType::MEMBER)
            ->exists();
    }
}
