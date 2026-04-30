<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Enums\PRFEntryType;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\QueryBuilder\AllowedFilter;

class ExpenseCategory extends Model implements HasQueryBuilderCapabilities
{
    use HasModelPermissions;
    use HasUlid;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'ulid',
        'name',
        'description',
        'is_active',
    ];

    public const INCLUDES = [
        'expenses',
    ];

    public const SORTS = ['created_at', 'updated_at'];

    /**
     * @return array<int, AllowedFilter>
     */
    public static function filters(): array
    {
        return [
            AllowedFilter::callback('status_key', function ($query, $value): void {
                $query->where('is_active', $value);
            }),
            AllowedFilter::callback('status_keys', function ($query, $value): void {
                $query->whereIn('status', Arr::wrap($value));
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
