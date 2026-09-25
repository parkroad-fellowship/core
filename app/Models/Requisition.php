<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Enums\PRFApprovalStatus;
use App\Enums\PRFResponsibleDesk;
use App\Helpers\Utils;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasULID;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\QueryBuilder\AllowedFilter;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

#[Fillable([
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
])]
class Requisition extends Model implements HasQueryBuilderCapabilities
{
    use BelongsToTenant;
    use HasModelPermissions;
    use HasULID;
    use LogsActivity;
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'requisition_date' => 'date',
            'review_requested_at' => 'date',
            'responsible_desk' => PRFResponsibleDesk::class,
            'total_amount' => 'integer',
            'approval_status' => PRFApprovalStatus::class,
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

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
                $query->where('appointed_approver_id', Member::query()->select('id')->where('ulid', $value)->limit(1));
            }),
            AllowedFilter::callback('accounting_event_ulid', function ($query, $value) {
                $query->where(
                    'accounting_event_id',
                    AccountingEvent::query()->select('id')->where('ulid', $value)->limit(1),
                );
            }),
            AllowedFilter::callback('member_ulid', function ($query, $value) {
                $query->where('member_id', Member::query()->select('id')->where('ulid', $value)->limit(1));
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

    /**
     * Members to tell about a decision on this requisition: the appointed approver, whoever
     * approved or rejected it, and the responsible desk (optionally the treasurer's desk and
     * the requester too). Desk addresses match members by either email.
     *
     * @return Collection<int, Member>
     */
    public function stakeholders(
        bool $includeRequester = false,
        bool $includeTreasury = false,
        ?int $formerApproverId = null,
    ): Collection {
        $memberIds = collect([$this->appointed_approver_id, $this->approved_by, $formerApproverId])
            ->when($includeRequester, fn($ids) => $ids->push($this->member_id))
            ->filter()
            ->unique();

        $deskEmails = collect(Utils::getDeskEmails($this->responsible_desk))
            ->when($includeTreasury, fn($emails) => $emails->merge(Utils::getDeskEmails(PRFResponsibleDesk::TREASURER_DESK)))
            ->filter()
            ->unique();

        return Member::query()
            ->where(
                fn($query) => $query
                    ->whereIn('id', $memberIds)
                    ->orWhereIn('email', $deskEmails)
                    ->orWhereIn('personal_email', $deskEmails),
            )
            ->get()
            ->unique('id')
            ->values();
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function appointedApprover(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'appointed_approver_id');
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'approved_by');
    }

    /**
     * @return BelongsTo<AccountingEvent, $this>
     */
    public function accountingEvent(): BelongsTo
    {
        return $this->belongsTo(AccountingEvent::class);
    }

    /**
     * @return HasMany<RequisitionItem, $this>
     */
    public function requisitionItems(): HasMany
    {
        return $this->hasMany(RequisitionItem::class);
    }

    /**
     * @return HasOne<PaymentInstruction, $this>
     */
    public function paymentInstruction(): HasOne
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
        return in_array($this->approval_status, [PRFApprovalStatus::APPROVED]);
    }
}
