<?php

namespace App\Models;

use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BudgetEstimateEntry extends Model
{
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
        'notes',
    ];

    protected $casts = [
        'unit_price' => 'integer',
        'quantity' => 'integer',
        'total_price' => 'integer',
    ];

    public const INCLUDES = [
        'budgetEstimate',
        'expenseCategory',
    ];

    public function budgetEstimate()
    {
        return $this->belongsTo(BudgetEstimate::class);
    }

    public function expenseCategory()
    {
        return $this->belongsTo(ExpenseCategory::class);
    }
}
