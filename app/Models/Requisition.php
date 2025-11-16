<?php

namespace App\Models;

use App\Observers\RequisitionObserver;
use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

#[ObservedBy(RequisitionObserver::class)]
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
        'appointed_approver_id',
        'approved_by',
        'approval_status',
        'approval_notes',
        'remarks',
        'total_amount',
        'approved_at',
        'rejected_at',
        'review_requested_at',
    ];

    protected $casts = [
        'requisition_date' => 'date',
        'review_requested_at' => 'date',
        'responsible_desk' => 'integer',
        'requisitionable_type' => 'integer',
        'total_amount' => 'integer',
        'approved_at' => 'datetime',
    ];

    public const INCLUDES = [
        'member',
        'appointedApprover',
        'approvedBy',
        'accountingEvent',
        'requisitionItems',
        'requisitionItems.expenseCategory',
        'paymentInstruction',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
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

    public function paymentInstruction()
    {
        return $this->hasOne(PaymentInstruction::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
