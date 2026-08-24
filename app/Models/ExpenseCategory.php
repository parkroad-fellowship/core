<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Enums\PRFActiveStatus;
use App\Enums\PRFEntryType;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\QueryBuilder\AllowedFilter;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class ExpenseCategory extends Model implements HasQueryBuilderCapabilities
{
    use BelongsToTenant;
    use HasModelPermissions;
    use HasUlid;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'ulid',
        'name',
        'description',
        'is_active',
        'is_per_person',
    ];

    protected function casts(): array
    {
        return [
            'is_per_person' => 'boolean',
            'is_active' => PRFActiveStatus::class,
        ];
    }

    public const INCLUDES = [
        'expenses',
    ];

    public const SORTS = ['created_at', 'updated_at'];

    public static function filters(): array
    {
        return [
            AllowedFilter::callback('status_key', function ($query, $value): void {
                $query->where('is_active', $value);
            }),
            AllowedFilter::callback('status_keys', function ($query, $value): void {
                $query->whereIn('is_active', Arr::wrap($value));
            }),
        ];
    }

    public function expenses()
    {
        return $this
            ->hasMany(AllocationEntry::class)
            ->where('entry_type', PRFEntryType::DEBIT);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
