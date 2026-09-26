<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Enums\PRFPledgeFrequency;
use App\Enums\PRFPledgeStatus;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasULID;
use Database\Factories\PledgeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\QueryBuilder\AllowedFilter;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

/**
 * A giving commitment people make on the public pledge page. For non-app
 * members the pledge itself is the identity (name/email/phone) even without
 * a User/Member record, so their giving history lives on the pledge.
 *
 */
#[Fillable([
    'member_id',
    'name',
    'email',
    'phone',
    'amount',
    'frequency',
    'start_date',
    'next_due_on',
    'last_fulfilled_on',
    'status',
])]
class Pledge extends Model implements HasQueryBuilderCapabilities
{
    use BelongsToTenant;
    /** @use HasFactory<PledgeFactory> */
    use HasFactory;
    use HasModelPermissions;
    use HasULID;
    use LogsActivity;
    use SoftDeletes;

    public const INCLUDES = [
        'member',
        'installments',
    ];

    public const SORTS = ['created_at', 'updated_at', 'name', 'amount', 'next_due_on'];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'frequency' => PRFPledgeFrequency::class,
            'status' => PRFPledgeStatus::class,
            'start_date' => 'date',
            'next_due_on' => 'date',
            'last_fulfilled_on' => 'date',
        ];
    }

    /**
     * @return array<int, AllowedFilter>
     */
    public static function filters(): array
    {
        return [
            AllowedFilter::exact('ulid'),
            AllowedFilter::exact('status'),
            AllowedFilter::callback('frequency', function ($query, int $value) {
                $query->where('frequency', $value);
            }),
            AllowedFilter::callback('member_ulid', function ($query, string $value) {
                $query->where('member_id', Member::query()->select('id')->where('ulid', $value)->limit(1));
            }),
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function installments(): HasMany
    {
        return $this->hasMany(PledgeInstallment::class);
    }

    /**
     * The commitment's expected contribution over a full year.
     */
    public function annualizedAmount(): float
    {
        $frequency = $this->frequency;

        if (!$frequency instanceof PRFPledgeFrequency) {
            $frequency = PRFPledgeFrequency::tryFrom((int) $frequency) ?? PRFPledgeFrequency::MONTHLY;
        }

        return (float) $this->amount * $frequency->getAnnualMultiplier();
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(PledgeReminder::class);
    }

    /**
     * Advance the due-date cadence after a fulfillment is recorded.
     *
     * Recurring pledges advance from their own due date (preserving
     * day-of-month) while the pledge remains active. One-time pledges are
     * marked fulfilled and have no further due date.
     */
    public function advanceDueDate(Carbon $fulfilledOn): void
    {
        $frequency = $this->frequency;

        if ($frequency && $frequency->value > 0 && $this->next_due_on) {
            $due = $this->next_due_on->copy();
            // Skip forward until the next unfulfilled due date after the payment.
            while ($due->lte($fulfilledOn)) {
                $due = $due->addMonths($frequency->value);
            }

            $this->update([
                'next_due_on' => $due,
                'last_fulfilled_on' => $fulfilledOn,
            ]);
            $this->refresh();
            return;
        }

        $this->update([
            'next_due_on' => null,
            'last_fulfilled_on' => $fulfilledOn,
            'status' => PRFPledgeStatus::FULFILLED->value,
        ]);
        $this->refresh();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }
}
