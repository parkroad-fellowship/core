<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Enums\PRFApprovalStatus;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasUlid;
use App\Observers\RequisitionObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\QueryBuilder\AllowedFilter;

#[ObservedBy(RequisitionObserver::class)]
class Requisition extends Model implements HasQueryBuilderCapabilities
{
    use HasModelPermissions;
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
        'remarks',
        'total_amount',
        'approval_status',
        'approval_notes',
        'approved_by',
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
        'approval_status' => 'integer',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
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

    public const SORTS = ['created_at', 'updated_at', 'requisition_date'];

    /**
     * @return array<int, AllowedFilter>
     */
    public static function filters(): array
    {
        return [
            AllowedFilter::callback('appointed_approver_ulid', function ($query, $value) {
                $query->where(
                    'appointed_approver_id',
                    Member::query()
                        ->select('id')
                        ->where('ulid', $value)
                        ->limit(1)
                );
            }),
            AllowedFilter::callback('accounting_event_ulid', function ($query, $value) {
                $query->where(
                    'accounting_event_id',
                    AccountingEvent::query()
                        ->select('id')
                        ->where('ulid', $value)
                        ->limit(1)
                );
            }),
            AllowedFilter::callback('member_ulid', function ($query, $value) {
                $query->where(
                    'member_id',
                    Member::query()
                        ->select('id')
                        ->where('ulid', $value)
                        ->limit(1)
                );
            }),
            AllowedFilter::callback('approval_status', function ($query, $value) {
                $query->where('approval_status', $value);
            }),
            AllowedFilter::callback('approval_statuses', function ($query, $value) {
                $query->whereIn('approval_status', Arr::wrap($value));
            }),
            AllowedFilter::callback('responsible_desk', function ($query, $value) {
                $query->where('responsible_desk', $value);
            }),
            AllowedFilter::callback('responsible_desks', function ($query, $value) {
                $query->whereIn('responsible_desk', Arr::wrap($value));
            }),
            AllowedFilter::callback('requisition_date', function ($query, $value) {
                $query->whereDate('requisition_date', $value);
            }),
        ];
    }

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

    public function canBeRecalled(): bool
    {
        // A requisition can be recalled if it is approved
        return in_array($this->approval_status, [
            PRFApprovalStatus::APPROVED->value,
        ]);
    }
}
