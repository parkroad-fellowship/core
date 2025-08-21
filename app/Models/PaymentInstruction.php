<?php

namespace App\Models;

use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentInstruction extends Model
{
    use HasUlid;
    use SoftDeletes;

    protected $fillable = [
        'ulid',
        'requisition_id',
        'payment_method',
        'recipient_name',
        'reference',
        'mpesa_phone_number',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'bank_branch',
        'bank_swift_code',
        'paybill_number',
        'paybill_account_number',
        'till_number',
        'amount',
    ];

    protected $casts = [
        'payment_method' => 'integer',
        'mpesa_phone_number' => 'integer',
        'bank_account_number' => 'integer',
        'paybill_number' => 'integer',
        'till_number' => 'integer',
        'amount' => 'integer',
    ];

    public const INCLUDES = [
        'requisition',
        'requisition.member',
        'requisition.accountingEvent',
    ];

    public function requisition()
    {
        return $this->belongsTo(Requisition::class);
    }
}
