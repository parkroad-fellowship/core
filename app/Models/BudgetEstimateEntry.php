<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasULID;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

#[Fillable([
    'ulid',
    'budget_estimate_id',
    'expense_category_id',
    'item_name',
    'unit_price',
    'quantity',
    'total_price',
    'cost',
    'notes',
])]
class BudgetEstimateEntry extends Model implements HasQueryBuilderCapabilities
{
    use BelongsToTenant;
    use HasModelPermissions;
    use HasULID;
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'unit_price' => 'integer',
            'quantity' => 'integer',
            'total_price' => 'integer',
            'cost' => 'integer',
        ];
    }

    public const INCLUDES = [
        'budgetEstimate',
        'expenseCategory',
    ];

    public const SORTS = ['created_at', 'updated_at'];

    public static function filters(): array
    {
        return [];
    }

    /**
     * @return BelongsTo<BudgetEstimate, $this>
     */
    public function budgetEstimate(): BelongsTo
    {
        return $this->belongsTo(BudgetEstimate::class);
    }

    /**
     * @return BelongsTo<ExpenseCategory, $this>
     */
    public function expenseCategory(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class);
    }
}
