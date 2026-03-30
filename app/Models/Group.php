<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Group extends Model implements HasQueryBuilderCapabilities
{
    use HasFactory;
    use HasModelPermissions;
    use HasUlid;
    use LogsActivity;
    use SoftDeletes;

    public const INCLUDES = [
        'groupMembers',
        'courseGroups',
        'groupMembers.member',
    ];

    public const SORTS = ['created_at', 'updated_at'];

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

    public function announcementGroups()
    {
        return $this->hasMany(AnnouncementGroup::class);
    }

    public static function filters(): array
    {
        return [];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
