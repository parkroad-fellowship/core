<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Enums\PRFMorphType;
use App\Enums\PRFTransactionType;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasULID;
use App\Observers\ExpenseObserver;
use Database\Factories\ExpenseFactory;
use Deprecated;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\QueryBuilder\AllowedFilter;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

// #[Deprecated('Use new AllocationEntry')]
// #[ObservedBy(ExpenseObserver::class)]
#[Fillable([
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
])]
class Expense extends Model implements HasMedia, HasQueryBuilderCapabilities
{
    use BelongsToTenant;
    use HasModelPermissions;
    /** @use HasFactory<ExpenseFactory> */
    use HasFactory;

    use HasULID;
    use InteractsWithMedia;
    use LogsActivity;
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'unit_cost' => 'integer',
            'quantity' => 'integer',
            'line_total' => 'integer',
            'charge_type' => PRFTransactionType::class,
            'expenseable_type' => PRFMorphType::class,
        ];
    }

    public const INCLUDES = [
        'member',
        'expenseCategory',
        'expenseable',
        'media',
        'receipts',
    ];

    public const SORTS = ['created_at', 'updated_at'];

    /**
     * @return array<int, AllowedFilter>
     */
    public static function filters(): array
    {
        return [];
    }

    public const MEDIA_COLLECTIONS = [
        self::RECEIPTS,
    ];

    public const RECEIPTS = 'receipts';

    /**
     * @return BelongsTo<Member, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * @return BelongsTo<ExpenseCategory, $this>
     */
    public function expenseCategory(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function expenseable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
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
