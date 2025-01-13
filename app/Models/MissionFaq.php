<?php

namespace App\Models;

use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class MissionFaq extends Model
{
    use HasFactory;
    use HasUlid;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'ulid',
        'question',
        'answer',
    ];

    public function studentEnquiries()
    {
        return $this->hasMany(StudentEnquiry::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
