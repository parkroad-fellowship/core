<?php

namespace App\Models;

use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Student extends Model
{
    use HasFactory;
    use HasUlid;
    use SoftDeletes;
    use LogsActivity;

    protected $fillable = [
        'ulid',
        'name',
        'user_id',
    ];

    protected $appends = [
        'email',
    ];

    public function getEmailAttribute()
    {
        return $this->name.'@parkroadfellowship.org';
    }

    public function studentEnquiries()
    {
        return $this->hasMany(StudentEnquiry::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
