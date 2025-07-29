<?php

namespace App\Models;

use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Requisition extends Model
{
    use HasUlid;
    use SoftDeletes;

    protected $fillable = [
        'ulid',
        'member_id',
        'requisitionable_id',
        'requisitionable_type',
        'requisition_date',
        'requisition_desk',
        'verified_by',
        'approved_by',
        'remarks',
        'total_amount',
        'verified_at',
        'approved_at',
    ];

    protected $casts = [
        'requisition_date' => 'date',
        'requisition_desk' => 'integer',
        'requisitionable_type' => 'integer',
        'total_amount' => 'integer',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function verifiedBy()
    {
        return $this->belongsTo(Member::class, 'verified_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(Member::class, 'approved_by');
    }

    public function requisitionable()
    {
        return $this->morphTo();
    }

    public function requisitionItems()
    {
        return $this->hasMany(RequisitionItem::class);
    }

    public function paymentInstructions()
    {
        return $this->hasMany(PaymentInstruction::class);
    }
}
