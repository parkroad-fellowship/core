<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Enums\PRFFinancialAccountType;
use App\Enums\PRFLedgerFlow;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasULID;
use Database\Factories\FinancialAccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\QueryBuilder\AllowedFilter;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

/**
 * Somewhere the fellowship holds money (Paybill, M-Pesa, bank, cash…). Its balance is the sum of its ledger.
 */
#[Fillable([
    'name',
    'type',
    'identifier',
    'description',
    'is_active',
])]
class FinancialAccount extends Model implements HasQueryBuilderCapabilities
{
    use BelongsToTenant;
    /** @use HasFactory<FinancialAccountFactory> */
    use HasFactory;
    use HasModelPermissions;
    use HasULID;
    use LogsActivity;
    use SoftDeletes;

    public const INCLUDES = ['ledgerEntries'];

    public const SORTS = ['created_at', 'updated_at', 'name'];

    protected function casts(): array
    {
        return [
            'type' => PRFFinancialAccountType::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return array<int, AllowedFilter>
     */
    public static function filters(): array
    {
        return [
            AllowedFilter::exact('type'),
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
     * Adds a `computed_balance` column: receipts minus payments, optionally up to a date.
     *
     * @param  Builder<FinancialAccount>  $query
     */
    #[Scope]
    protected function withBalance(Builder $query, ?Carbon $asOf = null): void
    {
        $query->addSelect([
            'computed_balance' => LedgerEntry::query()
                ->selectRaw(self::signedSumSql())
                ->whereColumn('ledger_entries.financial_account_id', 'financial_accounts.id')
                ->when($asOf, fn(Builder $entries) => $entries->whereDate('transacted_on', '<=', $asOf)),
        ]);
    }

    /**
     * @param  Builder<FinancialAccount>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @return Attribute<int, never>
     */
    protected function balance(): Attribute
    {
        return Attribute::get(
            fn(): int => (int) (
                $this->attributes['computed_balance'] ?? $this
                    ->ledgerEntries()
                    ->selectRaw(self::signedSumSql())
                    ->value('balance')
            ),
        );
    }

    public static function signedSumSql(string $alias = 'balance'): string
    {
        $receipt = PRFLedgerFlow::RECEIPT->value;

        return "COALESCE(SUM(CASE WHEN flow = {$receipt} THEN amount ELSE -amount END), 0) as {$alias}";
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }
}
