<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Enums\PRFAccountEventStatus;
use App\Enums\PRFEntryType;
use App\Enums\PRFMorphType;
use App\Enums\PRFReconciliationStatus;
use App\Enums\PRFResponsibleDesk;
use App\Enums\PRFTransactionType;
use App\Helpers\Utils;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasULID;
use Database\Factories\AccountingEventFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\QueryBuilder\AllowedFilter;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

#[Fillable([
    'accounting_eventable_id',
    'accounting_eventable_type',
    'name',
    'description',
    'due_date',
    'status',
    'responsible_desk',
    'reconciliation_status',
    'reconciliation_remarks',
    'reconciled_at',
    'reconciled_by',
])]
#[Hidden(['moneyEntries'])]
#[Appends([
    'spent_amount',
    'debits',
    'amount_received',
    'credits',
    'balance',
    'refund_charge',
    'amount_to_refund',
])]
class AccountingEvent extends Model implements HasQueryBuilderCapabilities
{
    use BelongsToTenant;
    /** @use HasFactory<AccountingEventFactory> */
    use HasFactory;
    use HasModelPermissions;
    use HasULID;
    use LogsActivity;
    use SoftDeletes;

    /** Always load the slim entries so money fields need no per-row queries. */
    protected $with = ['moneyEntries'];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'status' => PRFAccountEventStatus::class,
            'responsible_desk' => PRFResponsibleDesk::class,
            'accounting_eventable_type' => PRFMorphType::class,
            'reconciliation_status' => PRFReconciliationStatus::class,
            'reconciled_at' => 'datetime',
        ];
    }

    public const INCLUDES = [
        'requisitions',
        'accountingEventable',
        'refunds',
        'latestRefund',
        'allocationEntries',
    ];

    public const SORTS = ['created_at', 'updated_at', 'due_date'];

    /**
     * @return array<int, AllowedFilter>
     */
    public static function filters(): array
    {
        return [
            AllowedFilter::callback('status', function ($query, $value) {
                $query->where('status', $value);
            }),
            AllowedFilter::callback('responsible_desk', function ($query, $value) {
                $query->where('responsible_desk', $value);
            }),
            AllowedFilter::callback('due_date', function ($query, $value) {
                $query->whereDate('due_date', $value);
            }),
            AllowedFilter::callback('month', function ($query, $value) {
                $month = Carbon::parse((string) $value);
                $query->whereBetween('due_date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()]);
            }),
            AllowedFilter::exact('reconciliation_status'),
        ];
    }

    /**
     * @return HasMany<Requisition, $this>
     */
    public function requisitions(): HasMany
    {
        return $this->hasMany(Requisition::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function accountingEventable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reconciledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by');
    }

    /**
     * Cash movements in the treasurer's ledger linked to this event (disbursements, refunds, tokens).
     *
     * @return HasMany<LedgerEntry, $this>
     */
    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }

    /**
     * @return HasMany<AllocationEntry, $this>
     */
    public function allocationEntries(): HasMany
    {
        return $this->hasMany(AllocationEntry::class);
    }

    /**
     * Slim entries used only to total money fields in PHP. Kept separate from
     * allocationEntries() so loading it does not trigger the accountability
     * block in the API resource.
     *
     * @return HasMany<AllocationEntry, $this>
     */
    public function moneyEntries(): HasMany
    {
        return $this->hasMany(AllocationEntry::class)->select(['id', 'accounting_event_id', 'entry_type', 'amount']);
    }

    /**
     * @return HasMany<Refund, $this>
     */
    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    /**
     * @return HasOne<Refund, $this>
     */
    public function latestRefund(): HasOne
    {
        return $this->hasOne(Refund::class)->latestOfMany();
    }

    /**
     * Credit and debit totals, summed in PHP from moneyEntries. Lazy-loads the
     * relation once when it was not eager-loaded. Accessors below cache per
     * instance; refresh() reloads relations and clears that cache. Values are
     * cached per instance and the relation is eager-loaded; call refresh() after
     * writing entries to an already-loaded event.
     *
     * @return array{credits: int, debits: int}
     */
    protected function totals(): array
    {
        $entries = $this->moneyEntries;

        return [
            'credits' => (int) $entries->where('entry_type', PRFEntryType::CREDIT)->sum('amount'),
            'debits' => (int) $entries->where('entry_type', PRFEntryType::DEBIT)->sum('amount'),
        ];
    }

    protected function credits(): Attribute
    {
        return Attribute::make(get: fn() => $this->totals()['credits'])->shouldCache();
    }

    protected function debits(): Attribute
    {
        return Attribute::make(get: fn() => $this->totals()['debits'])->shouldCache();
    }

    protected function spentAmount(): Attribute
    {
        return Attribute::make(get: fn() => (int) $this->debits);
    }

    protected function amountReceived(): Attribute
    {
        return Attribute::make(get: fn() => (int) $this->credits);
    }

    protected function balance(): Attribute
    {
        return Attribute::make(get: fn(): int => (int) ($this->credits - $this->debits))->shouldCache();
    }

    protected function refundCharge(): Attribute
    {
        return Attribute::make(get: fn() => (int) Utils::getCharge(
            chargeType: PRFTransactionType::MPESA_PAYBILL_BUSINESS_TARRIFF,
            amount: $this->balance,
        ))->shouldCache();
    }

    protected function amountToRefund(): Attribute
    {
        return Attribute::make(get: fn() => (int) ($this->balance - $this->refund_charge));
    }
}
