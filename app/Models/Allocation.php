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

    public function allocationEntries()
    {
        return $this->hasMany(AllocationEntry::class);
    }

    public function getBalanceAttribute()
    {
        $credit = PRFEntryType::CREDIT->value;
        $debit = PRFEntryType::DEBIT->value;

        return AllocationEntry::where('allocation_id', $this->id)
            ->selectRaw("
                SUM(CASE WHEN entry_type = {$credit} THEN amount ELSE 0 END) - 
                SUM(CASE WHEN entry_type = {$debit} THEN amount ELSE 0 END) as balance
            ")
            ->value('balance') ?? 0;
    }

    public function getTotalSpendAttribute()
    {
        return AllocationEntry::where('allocation_id', $this->id)
            ->selectRaw('SUM(amount) as total_spend')
            ->value('total_spend') ?? 0;
    }
}
