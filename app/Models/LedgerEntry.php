<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Enums\PRFLedgerCategoryKind;
use App\Enums\PRFLedgerChannel;
use App\Enums\PRFLedgerFlow;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasULID;
use Database\Factories\LedgerEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\QueryBuilder\AllowedFilter;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

/**
 * One line of the treasurer's cashbook: money into or out of a financial account.
 *
 * Every shilling that moves is here — keyed in by the treasurer (offline income, expenses, charges)
 * or auto-posted from the app (Paystack gifts, disbursements, refunds, tokens). Amounts are whole KES
 * and always positive; `flow` gives the direction.
 */
#[Fillable([
    'financial_account_id',
    'ledger_category_id',
    'flow',
    'channel',
    'amount',
    'transacted_on',
    'counterparty',
    'description',
    'reference',
    'receipt_number',
    'member_id',
    'giver_email',
    'giver_phone',
    'accounting_event_id',
    'payment_id',
    'pledge_installment_id',
    'membership_id',
    'requisition_id',
    'refund_id',
    'allocation_entry_id',
    'account_transfer_id',
    'ledger_import_id',
    'source_key',
    'recorded_by',
])]
class LedgerEntry extends Model implements HasQueryBuilderCapabilities
{
    use BelongsToTenant;
    /** @use HasFactory<LedgerEntryFactory> */
    use HasFactory;
    use HasModelPermissions;
    use HasULID;
    use LogsActivity;
    use SoftDeletes;

    public const INCLUDES = [
        'financialAccount',
        'ledgerCategory',
        'member',
        'accountingEvent',
        'receiptDeliveries',
    ];

    public const SORTS = ['created_at', 'updated_at', 'transacted_on', 'amount'];

    protected function casts(): array
    {
        return [
            'flow' => PRFLedgerFlow::class,
            'channel' => PRFLedgerChannel::class,
            'amount' => 'integer',
            'transacted_on' => 'date',
        ];
    }

    /**
     * @return array<int, AllowedFilter>
     */
    public static function filters(): array
    {
        return [
            AllowedFilter::exact('flow'),
            AllowedFilter::exact('channel'),
            AllowedFilter::callback('financial_account_ulid', function ($query, $value) {
                $query->where('financial_account_id', FinancialAccount::query()->select('id')->where('ulid', $value));
            }),
            AllowedFilter::callback('ledger_category_ulid', function ($query, $value) {
                $query->where('ledger_category_id', LedgerCategory::query()->select('id')->where('ulid', $value));
            }),
            AllowedFilter::callback('kind', function ($query, $value) {
                $query->whereHas('ledgerCategory', fn($category) => $category->where('kind', $value));
            }),
            AllowedFilter::callback('from', fn($query, $value) => $query->whereDate('transacted_on', '>=', $value)),
            AllowedFilter::callback('to', fn($query, $value) => $query->whereDate('transacted_on', '<=', $value)),
            AllowedFilter::exact('receipt_number'),
        ];
    }

    /**
     * @return BelongsTo<FinancialAccount, $this>
     */
    public function financialAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class);
    }

    /**
     * @return BelongsTo<LedgerCategory, $this>
     */
    public function ledgerCategory(): BelongsTo
    {
        return $this->belongsTo(LedgerCategory::class);
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * @return BelongsTo<AccountingEvent, $this>
     */
    public function accountingEvent(): BelongsTo
    {
        return $this->belongsTo(AccountingEvent::class);
    }

    /**
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * @return BelongsTo<PledgeInstallment, $this>
     */
    public function pledgeInstallment(): BelongsTo
    {
        return $this->belongsTo(PledgeInstallment::class);
    }

    /**
     * @return BelongsTo<Membership, $this>
     */
    public function membership(): BelongsTo
    {
        return $this->belongsTo(Membership::class);
    }

    /**
     * @return BelongsTo<Requisition, $this>
     */
    public function requisition(): BelongsTo
    {
        return $this->belongsTo(Requisition::class);
    }

    /**
     * @return BelongsTo<Refund, $this>
     */
    public function refund(): BelongsTo
    {
        return $this->belongsTo(Refund::class);
    }

    /**
     * @return BelongsTo<AllocationEntry, $this>
     */
    public function allocationEntry(): BelongsTo
    {
        return $this->belongsTo(AllocationEntry::class);
    }

    /**
     * @return BelongsTo<AccountTransfer, $this>
     */
    public function accountTransfer(): BelongsTo
    {
        return $this->belongsTo(AccountTransfer::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * @return HasMany<ReceiptDelivery, $this>
     */
    public function receiptDeliveries(): HasMany
    {
        return $this->hasMany(ReceiptDelivery::class);
    }

    /**
     * @param  Builder<LedgerEntry>  $query
     */
    #[Scope]
    protected function ofKind(Builder $query, PRFLedgerCategoryKind ...$kinds): void
    {
        $query->whereHas('ledgerCategory', fn(Builder $category) => $category->whereIn('kind', array_map(
            fn(PRFLedgerCategoryKind $kind) => $kind->value,
            $kinds,
        )));
    }

    /**
     * @param  Builder<LedgerEntry>  $query
     */
    #[Scope]
    protected function between(Builder $query, Carbon $from, Carbon $to): void
    {
        $query->whereBetween('transacted_on', [$from->toDateString(), $to->toDateString()]);
    }

    /**
     * Receipted income (never refunds, transfers or opening balances).
     */
    public function isIncome(): bool
    {
        return $this->flow === PRFLedgerFlow::RECEIPT && $this->ledgerCategory?->kind === PRFLedgerCategoryKind::INCOME;
    }

    /**
     * Positive for money in, negative for money out.
     *
     * @return Attribute<int, never>
     */
    protected function signedAmount(): Attribute
    {
        return Attribute::get(fn(): int => $this->amount * $this->flow->sign());
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }
}
