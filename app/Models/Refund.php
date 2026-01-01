<?php

namespace App\Models;

use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Refund extends Model
{
    use HasUlid;
    use SoftDeletes;

    protected $fillable = [
        'ulid',
        'accounting_event_id',
        'amount',
        'charge',
        'deficit_amount',
        'confirmation_message',
    ];

    public const INCLUDES = [
        'accountingEvent',
    ];

    public function accountingEvent()
    {
        return $this->belongsTo(AccountingEvent::class);
    }
}
