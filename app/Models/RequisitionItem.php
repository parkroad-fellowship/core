<?php

namespace App\Models;

use App\Observers\RequisitionItemObserver;
use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

#[ObservedBy(RequisitionItemObserver::class)]
class RequisitionItem extends Model
{
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
