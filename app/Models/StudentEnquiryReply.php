<?php

namespace App\Models;

use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentEnquiryReply extends Model
{
    use HasFactory;
    use HasUlid;
    use SoftDeletes;

    protected $fillable = [
        'ulid',
        'student_enquiry_id',
        'moderatorable_id',
        'moderatorable_type',
        'content',
    ];

    public function studentEnquiry()
    {
        return $this->belongsTo(StudentEnquiry::class);
    }

    public function moderatorable()
    {
        return $this->morphTo();
    }
}
