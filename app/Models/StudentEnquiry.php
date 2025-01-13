<?php

namespace App\Models;

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
    use SoftDeletes;
    use LogsActivity;

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
}
