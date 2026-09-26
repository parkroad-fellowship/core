<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Enums\PRFMorphType;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasULID;
use App\Observers\StudentEnquiryReplyObserver;
use Database\Factories\StudentEnquiryReplyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\QueryBuilder\AllowedFilter;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

#[Fillable([
    'ulid',
    'student_enquiry_id',
    'commentorable_id',
    'commentorable_type',
    'content',
    'is_from_chat_bot',
    'chat_bot_payload',
])]
#[ObservedBy(StudentEnquiryReplyObserver::class)]
class StudentEnquiryReply extends Model implements HasQueryBuilderCapabilities
{
    use BelongsToTenant;
    /** @use HasFactory<StudentEnquiryReplyFactory> */
    use HasFactory;
    use HasModelPermissions;
    use HasULID;
    use LogsActivity;
    use SoftDeletes;

    public const INCLUDES = [
        'studentEnquiry',
        'commentorable',
    ];

    public const SORTS = ['created_at', 'updated_at'];

    /**
     * @return array<int, string|AllowedFilter>
     */
    public static function filters(): array
    {
        return [
            AllowedFilter::callback('student_enquiry_ulid', function ($query, $value) {
                $query->where(
                    'student_enquiry_id',
                    StudentEnquiry::query()->select('id')->where('ulid', $value)->limit(1),
                );
            }),
        ];
    }

    protected function casts(): array
    {
        return [
            'is_from_chat_bot' => 'boolean',
            'chat_bot_payload' => 'array',
            'commentorable_type' => PRFMorphType::class,
        ];
    }

    /**
     * @return BelongsTo<StudentEnquiry, $this>
     */
    public function studentEnquiry(): BelongsTo
    {
        return $this->belongsTo(StudentEnquiry::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function commentorable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
