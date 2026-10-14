<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasULID;
use App\Observers\AccountTransferObserver;
use Database\Factories\AccountTransferFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\QueryBuilder\AllowedFilter;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

/**
 * Money moved between two of the fellowship's own accounts (e.g. Paybill → Bank). Never income or expense;
 * only the charge is an expense.
 */
#[Fillable([
    'from_financial_account_id',
    'to_financial_account_id',
    'amount',
    'charge',
    'transferred_on',
    'reference',
    'description',
    'recorded_by',
])]
#[ObservedBy(AccountTransferObserver::class)]
class AccountTransfer extends Model implements HasQueryBuilderCapabilities
{
    use BelongsToTenant;
    /** @use HasFactory<AccountTransferFactory> */
    use HasFactory;
    use HasModelPermissions;
    use HasULID;
    use LogsActivity;
    use SoftDeletes;

    public const INCLUDES = ['fromAccount', 'toAccount', 'ledgerEntries'];

    public const SORTS = ['created_at', 'updated_at', 'transferred_on', 'amount'];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'charge' => 'integer',
            'transferred_on' => 'date',
        ];
    }

    /**
     * @return array<int, AllowedFilter>
     */
    public static function filters(): array
    {
        return [
            AllowedFilter::callback('from', fn($query, $value) => $query->whereDate('transferred_on', '>=', $value)),
            AllowedFilter::callback('to', fn($query, $value) => $query->whereDate('transferred_on', '<=', $value)),
        ];
    }

    /**
     * @return BelongsTo<FinancialAccount, $this>
     */
    public function fromAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'from_financial_account_id');
    }

    /**
     * @return BelongsTo<FinancialAccount, $this>
     */
    public function toAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'to_financial_account_id');
    }

    /**
     * @return HasMany<LedgerEntry, $this>
     */
    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }
}
