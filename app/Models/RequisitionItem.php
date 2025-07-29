<?php

namespace App\Models;

use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RequisitionItem extends Model
{
    use HasUlid;
    use SoftDeletes;

    protected $fillable = [
        'ulid',
        'requisition_id',
        'expense_category_id',
        'item_name',
        'unit_price',
        'quantity',
        'total_price',

    ];

    protected $casts = [
        'unit_price' => 'integer',
        'quantity' => 'integer',
        'total_price' => 'integer',
    ];

    public function requisition()
    {
        return $this->belongsTo(Requisition::class);
    }

    public function expenseCategory()
    {
        return $this->belongsTo(ExpenseCategory::class);
    }
}
