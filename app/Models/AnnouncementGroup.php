<?php

namespace App\Models;

use App\Models\Concerns\HasUlid;
use App\Observers\AnnouncementGroupObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[ObservedBy(AnnouncementGroupObserver::class)]
class AnnouncementGroup extends Model
{
    use HasFactory;
    use HasUlid;
    use LogsActivity;
    use SoftDeletes;

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
