<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BudgetEstimate extends Model implements HasQueryBuilderCapabilities
{
    use HasModelPermissions;
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

    public const SORTS = ['created_at', 'updated_at'];

    public static function filters(): array
    {
        return [];
    }

    public function budgetEstimatable()
    {
        return $this->morphTo();
    }

    public function budgetEstimateEntries()
    {
        return $this->hasMany(BudgetEstimateEntry::class);
    }
}
