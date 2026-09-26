<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Enums\PRFActiveStatus;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasULID;
use Database\Factories\SchoolTermFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\QueryBuilder\AllowedFilter;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

#[Fillable([
    'name',
    'year',
    'is_active',
])]
class SchoolTerm extends Model implements HasQueryBuilderCapabilities
{
    use BelongsToTenant;
    /** @use HasFactory<SchoolTermFactory> */
    use HasFactory;
    use HasModelPermissions;
    use HasULID;
    use LogsActivity;
    use SoftDeletes;

    public const INCLUDES = [
        'missions',
    ];

    public const SORTS = ['created_at', 'updated_at', 'name'];

    protected function casts(): array
    {
        return [
            'is_active' => PRFActiveStatus::class,
        ];
    }

    /**
     * @return HasMany<Mission, $this>
     */
    public function missions(): HasMany
    {
        return $this->hasMany(Mission::class);
    }

    public static function filters(): array
    {
        return [
            AllowedFilter::callback('status_key', function ($query, $value): void {
                $query->where('is_active', $value);
            }),
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
