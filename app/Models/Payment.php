<?php

namespace App\Models;

use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentFactory> */
    use HasFactory;

    use HasUlid;
    use SoftDeletes;

    /** @var array<string> */
    protected $fillable = [
        'payment_type_id',
        'member_id',
        'amount',
        'payment_status',
        'redirect_url',
        'order_meta',
        'transaction_meta',
    ];

    /** @var array<string> */
    protected $casts = [
        'order_meta' => 'array',
        'transaction_meta' => 'array',
    ];

    public const INCLUDES = [
        'paymentType',
        'member',
    ];

    public function paymentType()
    {
        return $this->belongsTo(PaymentType::class);
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
