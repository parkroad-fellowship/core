<?php

namespace App\Models;

use App\Enums\PRFMorphType;
use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class StudentEnquiry extends Model
{
    use HasFactory;
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
