<?php

namespace App\Models;

use App\Observers\ExpenseObserver;
use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(ExpenseObserver::class)]
class Expense extends Model
{
    /** @use HasFactory<\Database\Factories\ExpenseFactory> */
    use HasFactory;

    use HasUlid;
    use SoftDeletes;

    protected $fillable = [
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
    ];

    protected $casts = [
        'unit_cost' => 'integer',
        'quantity' => 'integer',
        'line_total' => 'integer',
    ];

    public const INCLUDES = [
        'member',
        'expenseCategory',
        'expenseable',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function expenseCategory()
    {
        return $this->belongsTo(ExpenseCategory::class);
    }

    public function expenseable()
    {
        return $this->morphTo();
    }

    // TODO: Fix this relation to link properly
    public function school()
    {
        return $this->hasManyThrough(
            related: School::class,
            through: Mission::class,
            secondKey: 'id',
        );
    }
}
