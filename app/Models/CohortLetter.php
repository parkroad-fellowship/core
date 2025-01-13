<?php

namespace App\Models;

use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CohortLetter extends Model
{
    use HasFactory;
    use HasUlid;
    use SoftDeletes;
    use LogsActivity;

    protected $fillable = [
        'ulid',
        'cohort_id',
        'letter_id',
    ];

    public function cohort()
    {
        return $this->belongsTo(Cohort::class);
    }

    public function letter()
    {
        return $this->belongsTo(Letter::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
