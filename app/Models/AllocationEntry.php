<?php

namespace App\Models;

use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class AllocationEntry extends Model implements HasMedia
{
    use HasUlid;
    use InteractsWithMedia;
    use SoftDeletes;

    protected $fillable = [
        'accounting_event_id',
        'allocation_id',
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
    ];

    const INCLUDES = [
        'accountingEvent',
        'allocation',
        'expenseCategory',
        'member',
        'receipts',
    ];

    public const RECEIPTS = 'allocation-entry-receipts';

    public const MEDIA_COLLECTIONS = [
        self::RECEIPTS,
    ];

    public function accountingEvent()
    {
        return $this->belongsTo(AccountingEvent::class);
    }

    public function allocation()
    {
        return $this->belongsTo(Allocation::class);
    }

    public function expenseCategory()
    {
        return $this->belongsTo(ExpenseCategory::class);
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection(self::RECEIPTS);
    }

    public function receipts()
    {
        return $this
            ->media()
            ->where('collection_name', self::RECEIPTS);
    }
}
