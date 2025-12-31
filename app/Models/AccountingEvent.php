<?php

namespace App\Models;

use App\Enums\PRFEntryType;
use App\Enums\PRFTransactionType;
use App\Helpers\Utils;
use App\Observers\AccountingEventObserver;
use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

#[ObservedBy([AccountingEventObserver::class])]
class AccountingEvent extends Model
{
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
        'spent_amount' => 'integer',
        'debits' => 'integer',
        'amount_received' => 'integer',
        'credits' => 'integer',
        'balance' => 'integer',
        'refund_charge' => 'integer',
        'amount_to_refund' => 'integer',
    ];

    public const INCLUDES = [
        'requisitions',
        'accountingEventable',
    ];

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

    protected function spentAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->debits,
        );
    }

    protected function debits(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->allocationEntries()
                ->where('entry_type', PRFEntryType::DEBIT->value)
                ->sum('amount'),
        );
    }

    protected function amountReceived(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->credits,
        );
    }

    protected function credits(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->allocationEntries()
                ->where('entry_type', PRFEntryType::CREDIT->value)
                ->sum('amount'),
        );
    }

    protected function balance(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->calculateBalance(),
        );
    }

    protected function refundCharge(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->calculateRefundCharge(),
        );
    }

    protected function amountToRefund(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->calculateAmountToRefund(),
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
