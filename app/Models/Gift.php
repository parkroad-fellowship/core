<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Enums\PRFActiveStatus;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasULID;
use Database\Factories\GiftFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\QueryBuilder\AllowedFilter;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

#[Fillable([
    'ulid',
    'name',
    'is_active',
])]
class Gift extends Model implements HasQueryBuilderCapabilities
{
    use BelongsToTenant;
    /** @use HasFactory<GiftFactory> */
    use HasFactory;
    use HasModelPermissions;
    use HasULID;
    use LogsActivity;
    use SoftDeletes;

    public const INCLUDES = [];

    public const SORTS = ['created_at', 'updated_at', 'name'];

    /**
     * @return array<int, AllowedFilter>
     */
    public static function filters(): array
    {
        return [
            AllowedFilter::callback('status_key', function ($query, $value): void {
                $query->where('is_active', $value);
            }),
        ];
    }

    protected function casts(): array
    {
        return [
            'is_active' => PRFActiveStatus::class,
        ];
    }

    /**
     * @return BelongsToMany<Member, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Member::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
