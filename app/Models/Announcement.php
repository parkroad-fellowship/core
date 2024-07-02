<?php

namespace App\Models;

use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Announcement extends Model
{
    use HasFactory;
    use HasUlid;
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
}
