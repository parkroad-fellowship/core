<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Enums\PRFLedgerCategoryKind;
use App\Enums\PRFResponsibleDesk;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasULID;
use Database\Factories\LedgerCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\QueryBuilder\AllowedFilter;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

/**
 * A line of the chart of accounts: what money was for ("Mission Contribution", "Prayer Desk Expenses").
 */
#[Fillable([
    'name',
    'code',
    'kind',
    'responsible_desk',
    'statement_line',
    'sort',
    'is_active',
])]
class LedgerCategory extends Model implements HasQueryBuilderCapabilities
{
    use BelongsToTenant;
    /** @use HasFactory<LedgerCategoryFactory> */
    use HasFactory;
    use HasModelPermissions;
    use HasULID;
    use LogsActivity;
    use SoftDeletes;

    public const INCLUDES = ['ledgerEntries'];

    public const SORTS = ['created_at', 'updated_at', 'name', 'sort'];

    protected function casts(): array
    {
        return [
            'kind' => PRFLedgerCategoryKind::class,
            'responsible_desk' => PRFResponsibleDesk::class,
            'is_active' => 'boolean',
            'sort' => 'integer',
        ];
    }

    /**
     * @return array<int, AllowedFilter>
     */
    public static function filters(): array
    {
        return [
            AllowedFilter::exact('kind'),
            AllowedFilter::exact('responsible_desk'),
            AllowedFilter::exact('is_active'),
        ];
    }

    /**
     * @return HasMany<LedgerEntry, $this>
     */
    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }

    /**
     * @param  Builder<LedgerCategory>  $query
     */
    #[Scope]
    protected function ofKind(Builder $query, PRFLedgerCategoryKind ...$kinds): void
    {
        $query->whereIn('kind', array_map(fn(PRFLedgerCategoryKind $kind) => $kind->value, $kinds));
    }

    /**
     * The statement line this category rolls up to (defaults to its own name).
     */
    public function statementLine(): string
    {
        return $this->statement_line ?: $this->name;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }
}
