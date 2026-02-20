<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Refund extends Model implements HasQueryBuilderCapabilities
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

    public const SORTS = ['created_at', 'updated_at'];

    public function accountingEvent()
    {
        return $this->belongsTo(AccountingEvent::class);
    }
}
