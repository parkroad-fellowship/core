<?php

namespace App\Models;

use App\Models\Concerns\HasUlid;
use App\Observers\ExpenseObserver;
use Database\Factories\ExpenseFactory;
use Deprecated;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

// #[Deprecated('Use new AllocationEntry')]
// #[ObservedBy(ExpenseObserver::class)]
class Expense extends Model implements HasMedia
{
    /** @use HasFactory<ExpenseFactory> */
    use HasFactory;

    use HasUlid;
    use InteractsWithMedia;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'ulid',
        'member_id',
        'expense_category_id',
        'charge_type',
        'expenseable_id',
        'expenseable_type',
        'unit_cost',
        'quantity',
        'line_total',
        'charge',
        'confirmation_message',
        'narration',
    ];

    protected $casts = [
        'unit_cost' => 'integer',
        'quantity' => 'integer',
        'line_total' => 'integer',
    ];

    public const INCLUDES = [
        'member',
        'expenseCategory',
        'expenseable',
        'media',
        'receipts',
    ];

    public const MEDIA_COLLECTIONS = [
        self::RECEIPTS,
    ];

    public const RECEIPTS = 'receipts';

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function expenseCategory()
    {
        return $this->belongsTo(ExpenseCategory::class);
    }

    public function expenseable()
    {
        return $this->morphTo();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
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
