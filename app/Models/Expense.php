<?php

namespace App\Models;

use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        'channel_type',
        'charge_type',
        'expenseable_id',
        'expenseable_type',
        'amount',
        'charge',
        'confirmation_message',
    ];

    protected $casts = [
        'amount' => 'integer',
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
