<?php

namespace App\Models;

use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Group extends Model
{
    use HasFactory;
    use HasUlid;
    use SoftDeletes;

    protected $fillable = [
        'ulid',
        'name',
        'description',
        'official_whatsapp_link',
        'is_active',
    ];

    public function courseGroups()
    {
        return $this->hasMany(CourseGroup::class);
    }

    public function groupMembers()
    {
        return $this->hasMany(GroupMember::class);
    }
}
