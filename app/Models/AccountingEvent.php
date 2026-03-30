<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Enums\PRFEntryType;
use App\Enums\PRFTransactionType;
use App\Helpers\Utils;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasUlid;
use App\Observers\AccountingEventObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\QueryBuilder\AllowedFilter;

#[ObservedBy([AccountingEventObserver::class])]
class AccountingEvent extends Model implements HasQueryBuilderCapabilities
{
    use HasModelPermissions;
    use HasUlid;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'accounting_eventable_id',
        'accounting_eventable_type',
        'name',
        'description',
        'due_date',
        'status',
        'responsible_desk',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

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

    protected $appends = [
        'spent_amount',
        'debits',
        'amount_received',
        'credits',
        'balance',
        'refund_charge',
        'amount_to_refund',
    ];

    public function requisitions()
    {
        return $this->hasMany(Requisition::class);
    }

    public function accountingEventable()
    {
        return $this->morphTo();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

    public function allocationEntries()
    {
        return $this->hasMany(AllocationEntry::class);
    }

    public function refunds()
    {
        return $this->hasMany(Refund::class);
    }

    public function latestRefund()
    {
        return $this
            ->hasOne(Refund::class)
            ->latestOfMany();
    }

    protected function spentAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => (int) $this->debits,
        );
    }

    protected function debits(): Attribute
    {
        return Attribute::make(
            get: fn () => (int) $this->allocationEntries()
                ->where('entry_type', PRFEntryType::DEBIT->value)
                ->sum('amount'),
        );
    }

    protected function amountReceived(): Attribute
    {
        return Attribute::make(
            get: fn () => (int) $this->credits,
        );
    }

    protected function credits(): Attribute
    {
        return Attribute::make(
            get: fn () => (int) $this->allocationEntries()
                ->where('entry_type', PRFEntryType::CREDIT->value)
                ->sum('amount'),
        );
    }

    protected function balance(): Attribute
    {
        return Attribute::make(
            get: fn () => (int) $this->calculateBalance(),
        );
    }

    protected function refundCharge(): Attribute
    {
        return Attribute::make(
            get: fn () => (int) $this->calculateRefundCharge(),
        );
    }

    protected function amountToRefund(): Attribute
    {
        return Attribute::make(
            get: fn () => (int) $this->calculateAmountToRefund(),
        );
    }

    protected function calculateBalance()
    {
        $credits = $this->allocationEntries()
            ->where('entry_type', PRFEntryType::CREDIT->value)
            ->sum('amount');

        $debits = $this->allocationEntries()
            ->where('entry_type', PRFEntryType::DEBIT->value)
            ->sum('amount');

        return $credits - $debits;
    }

    protected function calculateRefundCharge()
    {
        return Utils::getCharge(
            chargeType: PRFTransactionType::MPESA_PAYBILL_BUSINESS_TARRIFF,
            amount: $this->balance,
        );
    }

    protected function calculateAmountToRefund()
    {
        return $this->balance - $this->refund_charge;
    }
}
