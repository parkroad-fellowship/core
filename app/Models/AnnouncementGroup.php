<?php

namespace App\Models;

use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AnnouncementGroup extends Model
{
    use HasFactory;
    use HasUlid;
    use SoftDeletes;

    protected $fillable = [
        'announcement_id',
        'group_id',
    ];

    public function announcement()
    {
        return $this->belongsTo(Announcement::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }
}
