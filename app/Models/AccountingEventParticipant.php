<?php

namespace App\Models;

use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountingEventParticipant extends Model
{
    use HasUlid;
    use SoftDeletes;

    protected $fillable = [
        'accounting_event_id',
        'member_id',
    ];

    public function accountingEvent()
    {
        return $this->belongsTo(AccountingEvent::class);
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
