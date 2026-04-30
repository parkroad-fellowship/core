<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasUlid;
use App\Observers\RequisitionItemObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\QueryBuilder\AllowedFilter;

#[ObservedBy(RequisitionItemObserver::class)]
class RequisitionItem extends Model implements HasQueryBuilderCapabilities
{
    use HasModelPermissions;
    use HasUlid;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'ulid',
        'requisition_id',
        'expense_category_id',
        'item_name',
        'narration',
        'unit_price',
        'quantity',
        'total_price',

    ];

    protected $casts = [
        'unit_price' => 'integer',
        'quantity' => 'integer',
        'total_price' => 'integer',
    ];

    public const INCLUDES = [
        'requisition',
        'requisition.member',
        'requisition.accountingEvent',
        'expenseCategory',
    ];

    public const SORTS = ['created_at', 'updated_at', 'item_name', 'total_price'];

    /**
     * @return array<int, AllowedFilter>
     */
    public static function filters(): array
    {
        return [
            AllowedFilter::callback('requisition_ulid', function ($query, $value) {
                $query->where(
                    'requisition_id',
                    Requisition::query()
                        ->select('id')
                        ->where('ulid', $value)
                        ->limit(1)
                );
            }),
            AllowedFilter::callback('expense_category_ulid', function ($query, $value) {
                $query->where(
                    'expense_category_id',
                    ExpenseCategory::query()
                        ->select('id')
                        ->where('ulid', $value)
                        ->limit(1)
                );
            }),
            AllowedFilter::callback('item_name', function ($query, $value) {
                $query->where('item_name', 'like', '%'.$value.'%');
            }),
        ];
    }

    public function requisition()
    {
        return $this->belongsTo(Requisition::class);
    }

    public function expenseCategory()
    {
        return $this->belongsTo(ExpenseCategory::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
