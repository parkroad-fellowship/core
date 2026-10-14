<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Enums\PRFMorphType;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasULID;
use App\Observers\StudentEnquiryObserver;
use Database\Factories\StudentEnquiryFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\QueryBuilder\AllowedFilter;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

#[Fillable([
    'ulid',
    'student_id',
    'mission_faq_id',
    'content',
])]
#[Appends([
    'has_replies',
])]
#[ObservedBy(StudentEnquiryObserver::class)]
class StudentEnquiry extends Model implements HasQueryBuilderCapabilities
{
    use BelongsToTenant;
    /** @use HasFactory<StudentEnquiryFactory> */
    use HasFactory;
    use HasModelPermissions;
    use HasULID;
    use LogsActivity;
    use SoftDeletes;

    public const INCLUDES = [
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
                $query->where('student_id', Student::query()->select('id')->where('ulid', $value)->limit(1));
            }),
            AllowedFilter::callback('mission_faq_ulid', function ($query, $value) {
                $query->where('mission_faq_id', MissionFaq::query()->select('id')->where('ulid', $value)->limit(1));
            }),
        ];
    }

    /**
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * @return BelongsTo<MissionFaq, $this>
     */
    public function missionFaq(): BelongsTo
    {
        return $this->belongsTo(MissionFaq::class);
    }

    /**
     * @return HasMany<StudentEnquiryReply, $this>
     */
    public function studentEnquiryReplies(): HasMany
    {
        return $this->hasMany(StudentEnquiryReply::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

    public function getHasRepliesAttribute()
    {
        return $this->studentEnquiryReplies()->where('commentorable_type', PRFMorphType::MEMBER)->exists();
    }
}
