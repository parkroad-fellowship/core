<?php

namespace App\Models;

use App\Enums\PRFEntryType;
use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

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
    ];

    public const INCLUDES = [
        'requisitions',
        'accountingEventable',
        'participants',
        'participants.member',
    ];

    protected $appends = [
        'balance',
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

    public function participants()
    {
        return $this->hasMany(AccountingEventParticipant::class);
    }
}
