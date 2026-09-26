<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasULID;
use Database\Factories\AnnouncementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\QueryBuilder\AllowedFilter;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

#[Fillable([
    'title',
    'content',
    'published_at',
])]
class Announcement extends Model implements HasQueryBuilderCapabilities
{
    use BelongsToTenant;
    /** @use HasFactory<AnnouncementFactory> */
    use HasFactory;
    use HasModelPermissions;
    use HasULID;
    use LogsActivity;
    use SoftDeletes;

    public const INCLUDES = [
        'announcementGroups',
    ];

    public const SORTS = ['created_at', 'updated_at', 'published_at'];

    /**
     * @return array<int, string|AllowedFilter>
     */
    public static function filters(): array
    {
        return [
            AllowedFilter::callback('group_ulids', function ($query, $value) {
                return $query->whereHas('announcementGroups', function ($query) use ($value) {
                    return $query->whereIn(
                        'group_id',
                        Group::query()->whereIn('ulid', Arr::wrap($value))->select('id'),
                    );
                });
            }),
            AllowedFilter::scope('upcoming'),
            AllowedFilter::scope('past'),
        ];
    }

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<AnnouncementGroup, $this>
     */
    public function announcementGroups(): HasMany
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
