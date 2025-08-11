<?php

namespace App\Models;

use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Requisition extends Model
{
    use HasUlid;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'ulid',
        'member_id',
        'accounting_event_id',
        'requisition_date',
        'responsible_desk',
        'verified_by',
        'appointed_approver_id',
        'approved_by',
        'approval_status',
        'approval_notes',
        'remarks',
        'total_amount',
        'verified_at',
        'approved_at',
    ];

    protected $casts = [
        'requisition_date' => 'date',
        'responsible_desk' => 'integer',
        'requisitionable_type' => 'integer',
        'total_amount' => 'integer',
        'verified_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public const INCLUDES = [
        'member',
        'verifiedBy',
        'appointedApprover',
        'approvedBy',
        'accountingEvent',
        'requisitionItems',
        'requisitionItems.expenseCategory',
        'paymentInstructions',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function verifiedBy()
    {
        return $this->belongsTo(Member::class, 'verified_by');
    }

    public function appointedApprover()
    {
        return $this->belongsTo(Member::class, 'appointed_approver_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(Member::class, 'approved_by');
    }

    public function accountingEvent()
    {
        return $this->belongsTo(AccountingEvent::class);
    }

    public function requisitionItems()
    {
        return $this->hasMany(RequisitionItem::class);
    }

    public function paymentInstructions()
    {
        return $this->hasMany(PaymentInstruction::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
