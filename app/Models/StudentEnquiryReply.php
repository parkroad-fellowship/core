<?php

namespace App\Models;

use App\Observers\StudentEnquiryReplyObserver;
use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

#[ObservedBy(StudentEnquiryReplyObserver::class)]
class StudentEnquiryReply extends Model
{
    use HasFactory;
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
