<?php

namespace App\Models;

use App\Enums\PRFEntryType;
use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Allocation extends Model
{
    use HasUlid;
    use SoftDeletes;

    protected $fillable = [
        'ulid',
        'accounting_event_id',
        'requisition_id',
        'amount',
    ];

    protected $appends = [
        'balance',
        'total_spend',
    ];

    const INCLUDES = [
        'accountingEvent',
        'allocationEntries',
    ];

    public function accountingEvent()
    {
        return $this->belongsTo(AccountingEvent::class);
    }

    public function requisition()
    {
        return $this->belongsTo(Requisition::class);
    }

    public function allocationEntries()
    {
        return $this->hasMany(AllocationEntry::class);
    }

    public function getBalanceAttribute()
    {
        $credits = $this->allocationEntries()
            ->where('entry_type', PRFEntryType::CREDIT->value)
            ->sum('amount');

        $debits = $this->allocationEntries()
            ->where('entry_type', PRFEntryType::DEBIT->value)
            ->sum('amount');

        return $credits - $debits;
    }

    public function getTotalSpendAttribute()
    {
        return AllocationEntry::where('allocation_id', $this->id)
            ->selectRaw('SUM(amount) as total_spend')
            ->value('total_spend') ?? 0;
    }
}
