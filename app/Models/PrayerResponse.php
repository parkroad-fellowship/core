<?php

namespace App\Models;

use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PrayerResponse extends Model
{
    use HasFactory;
    use HasUlid;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'prayer_prompt_id',
        'member_id',
    ];

    const INCLUDES = [
        'prayerPrompt',
        'member',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function prayerPrompt()
    {
        return $this->belongsTo(PrayerPrompt::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
