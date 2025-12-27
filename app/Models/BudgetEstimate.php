<?php

namespace App\Models;

use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BudgetEstimate extends Model
{
    use HasUlid;
    use SoftDeletes;

    protected $fillable = [
        'ulid',
        'budget_estimatable_id',
        'budget_estimatable_type',
        'grand_total',
        'is_active',
    ];

    public const INCLUDES = [
        'budgetEstimatable',
        'budgetEstimateEntries',
        'budgetEstimateEntries.expenseCategory',
    ];

    public function budgetEstimatable()
    {
        return $this->morphTo();
    }

    public function budgetEstimateEntries()
    {
        return $this->hasMany(BudgetEstimateEntry::class);
    }
}
