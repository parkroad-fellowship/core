<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Models\Concerns\HasULID;
use Database\Factories\MissionExpenseFactory;
use Deprecated;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\QueryBuilder\AllowedFilter;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

// #[Deprecated('Use new AccountingEvent')]
#[Fillable([
    'ulid',
    'mission_id',
    'amount_received',
    'amount_spent',
    'token_amount',
    'amount_to_refund',
    'amount_refunded',
    'is_refunded',
    'balance',
    'refund_charge',
])]
class MissionExpense extends Model implements HasQueryBuilderCapabilities
{
    /** @use HasFactory<MissionExpenseFactory> */
    use BelongsToTenant;
    use HasFactory;

    use HasULID;
    use LogsActivity;
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'amount_received' => 'integer',
            'token_amount' => 'integer',
            'amount_to_refund' => 'integer',
            'amount_refunded' => 'integer',
            'is_refunded' => 'boolean',
            'balance' => 'integer',
            'amount_spent' => 'integer',
            'refund_charge' => 'integer',
        ];
    }

    public const INCLUDES = [
        'mission',
        'expenses',
        'expenses.expenseCategory',
        'expenses.receipts',
    ];

    public const SORTS = ['created_at', 'updated_at'];

    /**
     * @return array<int, AllowedFilter>
     */
    public static function filters(): array
    {
        return [];
    }

    /**
     * @return BelongsTo<Mission, $this>
     */
    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    /**
     * @return MorphMany<Expense, $this>
     */
    public function expenses(): MorphMany
    {
        return $this->morphMany(Expense::class, 'expenseable');
    }

    // TODO: Fix this relation to link properly

    /**
     * @return HasManyThrough<School, $this>
     */
    public function school(): HasManyThrough
    {
        return $this->hasManyThrough(
            School::class,
            Mission::class,
            'id', // Foreign key on the mission_expenses table...
            'id', // Foreign key on the missions table...
            'mission_id', // Local key on the mission_expenses table...
            'id', // Local key on the schools table...
        );
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
