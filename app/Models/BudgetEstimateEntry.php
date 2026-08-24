<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class BudgetEstimateEntry extends Model implements HasQueryBuilderCapabilities
{
    use BelongsToTenant;
    use HasModelPermissions;
    use HasUlid;
    use SoftDeletes;

    protected $fillable = [
        'ulid',
        'budget_estimate_id',
        'expense_category_id',
        'item_name',
        'unit_price',
        'quantity',
        'total_price',
        'cost',
        'notes',
    ];

    protected $casts = [
        'unit_price' => 'integer',
        'quantity' => 'integer',
        'total_price' => 'integer',
        'cost' => 'integer',
    ];

    public const INCLUDES = [
        'budgetEstimate',
        'expenseCategory',
    ];

    public const SORTS = ['created_at', 'updated_at'];

    public static function filters(): array
    {
        return [];
    }

    public function budgetEstimate()
    {
        return $this->belongsTo(BudgetEstimate::class);
    }

    public function expenseCategory()
    {
        return $this->belongsTo(ExpenseCategory::class);
    }
}
