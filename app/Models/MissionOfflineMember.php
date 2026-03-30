<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\QueryBuilder\AllowedFilter;

class MissionOfflineMember extends Model implements HasQueryBuilderCapabilities
{
    use HasFactory;
    use HasModelPermissions;
    use HasUlid;
    use LogsActivity;
    use SoftDeletes;

    public const INCLUDES = [
        'mission',
    ];

    public const SORTS = ['created_at', 'updated_at'];

    protected $fillable = [
        'mission_id',
        'name',
        'phone',
    ];

    /**
     * @return array<int, AllowedFilter>
     */
    public static function filters(): array
    {
        return [
            AllowedFilter::exact('ulid'),
            AllowedFilter::callback('mission_ulid', function ($query, $value) {
                $query->where(
                    'mission_id',
                    Mission::query()
                        ->select('id')
                        ->where('ulid', $value)
                        ->limit(1)
                );
            }),
        ];
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }
}
