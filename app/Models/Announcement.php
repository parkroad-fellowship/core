<?php

namespace App\Models;

use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Announcement extends Model
{
    use HasFactory;
    use HasUlid;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'title',
        'content',
        'published_at',
    ];

    const INCLUDES = [
        'announcementGroups',
    ];

    public function announcementGroups()
    {
        return $this->hasMany(AnnouncementGroup::class);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('published_at', '>=', now());
    }

    public function scopePast($query)
    {
        return $query->where('published_at', '<', now());
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
