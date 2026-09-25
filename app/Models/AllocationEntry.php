<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Enums\PRFEntryType;
use App\Enums\PRFTransactionType;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasULID;
use App\Observers\AllocationEntryObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\QueryBuilder\AllowedFilter;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

#[Fillable([
    'accounting_event_id',
    'requisition_id',
    'expense_category_id',
    'member_id',
    'entry_type',
    'amount',
    'charge_type',
    'unit_cost',
    'quantity',
    'charge',
    'narration',
    'confirmation_message',
])]
#[ObservedBy(AllocationEntryObserver::class)]
class AllocationEntry extends Model implements HasMedia, HasQueryBuilderCapabilities
{
    use BelongsToTenant;
    use HasModelPermissions;
    use HasULID;
    use InteractsWithMedia;
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'entry_type' => PRFEntryType::class,
            'charge_type' => PRFTransactionType::class,
        ];
    }

    public const INCLUDES = [
        'accountingEvent',
        'accountingEvent.refunds',
        'accountingEvent.latestRefund',
        'expenseCategory',
        'member',
        'receipts',
    ];

    public const SORTS = ['created_at', 'updated_at', 'amount'];

    /**
     * @return array<int, AllowedFilter>
     */
    public static function filters(): array
    {
        return [
            AllowedFilter::callback('accounting_event_ulid', function ($query, $value) {
                $query->where(
                    'accounting_event_id',
                    AccountingEvent::query()->select('id')->where('ulid', $value)->limit(1),
                );
            }),
            AllowedFilter::callback('requisition_ulid', function ($query, $value) {
                $query->where('requisition_id', Requisition::query()->select('id')->where('ulid', $value)->limit(1));
            }),
            AllowedFilter::callback('expense_category_ulid', function ($query, $value) {
                $query->where(
                    'expense_category_id',
                    ExpenseCategory::query()->select('id')->where('ulid', $value)->limit(1),
                );
            }),
        ];
    }

    public const RECEIPTS = 'allocation-entry-receipts';

    public const MEDIA_COLLECTIONS = [
        self::RECEIPTS,
    ];

    /**
     * @return BelongsTo<AccountingEvent, $this>
     */
    public function accountingEvent(): BelongsTo
    {
        return $this->belongsTo(AccountingEvent::class);
    }

    /**
     * @return BelongsTo<ExpenseCategory, $this>
     */
    public function expenseCategory(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class);
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::RECEIPTS);
    }

    /**
     * @return MorphMany<Media, $this>
     */
    public function receipts(): MorphMany
    {
        return $this->media()->where('collection_name', self::RECEIPTS);
    }
}
