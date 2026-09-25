<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Enums\PRFAccountEventStatus;
use App\Enums\PRFEntryType;
use App\Enums\PRFMorphType;
use App\Enums\PRFResponsibleDesk;
use App\Enums\PRFTransactionType;
use App\Helpers\Utils;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasULID;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
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
])]
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
    use HasModelPermissions;
    use HasULID;
    use LogsActivity;
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'status' => PRFAccountEventStatus::class,
            'responsible_desk' => PRFResponsibleDesk::class,
            'accounting_eventable_type' => PRFMorphType::class,
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
     * @return HasMany<AllocationEntry, $this>
     */
    public function allocationEntries(): HasMany
    {
        return $this->hasMany(AllocationEntry::class);
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

    protected function spentAmount(): Attribute
    {
        return Attribute::make(get: fn() => (int) $this->debits);
    }

    protected function debits(): Attribute
    {
        return Attribute::make(
            get: fn() => (int) $this->allocationEntries()->where('entry_type', PRFEntryType::DEBIT)->sum('amount'),
        );
    }

    protected function amountReceived(): Attribute
    {
        return Attribute::make(get: fn() => (int) $this->credits);
    }

    protected function credits(): Attribute
    {
        return Attribute::make(
            get: fn() => (int) $this->allocationEntries()->where('entry_type', PRFEntryType::CREDIT)->sum('amount'),
        );
    }

    protected function balance(): Attribute
    {
        return Attribute::make(get: fn() => (int) $this->calculateBalance());
    }

    protected function refundCharge(): Attribute
    {
        return Attribute::make(get: fn() => (int) $this->calculateRefundCharge());
    }

    protected function amountToRefund(): Attribute
    {
        return Attribute::make(get: fn() => (int) $this->calculateAmountToRefund());
    }

    protected function calculateBalance()
    {
        $credits = $this->allocationEntries()->where('entry_type', PRFEntryType::CREDIT)->sum('amount');

        $debits = $this->allocationEntries()->where('entry_type', PRFEntryType::DEBIT)->sum('amount');

        return $credits - $debits;
    }

    protected function calculateRefundCharge()
    {
        return Utils::getCharge(chargeType: PRFTransactionType::MPESA_PAYBILL_BUSINESS_TARRIFF, amount: $this->balance);
    }

    protected function calculateAmountToRefund()
    {
        return $this->balance - $this->refund_charge;
    }
}
