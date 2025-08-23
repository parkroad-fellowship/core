<?php

namespace App\Models;

use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AllocationEntry extends Model
{
    use HasUlid;
    use SoftDeletes;

    protected $fillable = [
        'accounting_event_id',
        'allocation_id',
        'expense_category_id',
        'member_id',
        'entry_type',
        'amount',
        'charge_type',
        'unit_cost',
        'quantity',
        'charge',
        'narration',
    ];

    const INCLUDES = [
        'accountingEvent',
        'allocation',
        'expenseCategory',
        'member',
    ];

    public function accountingEvent()
    {
        return $this->belongsTo(AccountingEvent::class);
    }

    public function allocation()
    {
        return $this->belongsTo(Allocation::class);
    }

    public function expenseCategory()
    {
        return $this->belongsTo(ExpenseCategory::class);
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
