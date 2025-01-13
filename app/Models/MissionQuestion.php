<?php

namespace App\Models;

use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class MissionQuestion extends Model
{
    use HasFactory;
    use HasUlid;
    use SoftDeletes;
    use LogsActivity;

    protected $fillable = [
        'ulid',
        'mission_id',
        'question',
    ];

    const INCLUDES = [
        'mission',
    ];

    public function mission()
    {
        return $this->belongsTo(Mission::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
