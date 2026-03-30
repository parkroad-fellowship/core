<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasUlid;
use App\Observers\StudentEnquiryReplyObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\QueryBuilder\AllowedFilter;

#[ObservedBy(StudentEnquiryReplyObserver::class)]
class StudentEnquiryReply extends Model implements HasQueryBuilderCapabilities
{
    use HasFactory;
    use HasModelPermissions;
    use HasUlid;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'ulid',
        'student_enquiry_id',
        'commentorable_id',
        'commentorable_type',
        'content',
        'is_from_chat_bot',
        'chat_bot_payload',
    ];

    const INCLUDES = [
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
                    StudentEnquiry::query()
                        ->select('id')
                        ->where('ulid', $value)
                        ->limit(1)
                );
            }),
        ];
    }

    protected $casts = [
        'is_from_chat_bot' => 'boolean',
        'chat_bot_payload' => 'array',
    ];

    public function studentEnquiry()
    {
        return $this->belongsTo(StudentEnquiry::class);
    }

    public function commentorable()
    {
        return $this->morphTo();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
