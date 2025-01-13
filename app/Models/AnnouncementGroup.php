<?php

namespace App\Models;

use App\Observers\AnnouncementGroupObserver;
use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

#[ObservedBy(AnnouncementGroupObserver::class)]
class AnnouncementGroup extends Model
{
    use HasFactory;
    use HasUlid;
    use SoftDeletes;
    use LogsActivity;

    protected $fillable = [
        'announcement_id',
        'group_id',
    ];

    const INCLUDES = [
        'announcement',
        'group',
    ];

    public function announcement()
    {
        return $this->belongsTo(Announcement::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
