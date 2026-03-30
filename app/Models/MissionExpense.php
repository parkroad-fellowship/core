<?php

namespace App\Models;

use App\Models\Concerns\HasUlid;
use Database\Factories\MissionExpenseFactory;
use Deprecated;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

// #[Deprecated('Use new AccountingEvent')]
class MissionExpense extends Model
{
    /** @use HasFactory<MissionExpenseFactory> */
    use HasFactory;

    use HasUlid;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'ulid',
        'mission_id',
        'amount_received',
        'amount_spent',
        'token_amount',
        'amount_to_refund',
        'amount_refunded',
        'is_refunded',
        'balance',
        'refund_charge',
    ];

    protected $casts = [
        'amount_received' => 'integer',
        'token_amount' => 'integer',
        'amount_to_refund' => 'integer',
        'amount_refunded' => 'integer',
        'is_refunded' => 'boolean',
        'balance' => 'integer',
        'amount_spent' => 'integer',
        'refund_charge' => 'integer',
    ];

    public const INCLUDES = [
        'mission',
        'expenses',
        'expenses.expenseCategory',
        'expenses.receipts',
    ];

    public function mission()
    {
        return $this->belongsTo(Mission::class);
    }

    public function expenses()
    {
        return $this->morphMany(Expense::class, 'expenseable');
    }

    // TODO: Fix this relation to link properly

    public function school()
    {
        return $this->hasManyThrough(
            School::class,
            Mission::class,
            'id', // Foreign key on the mission_expenses table...
            'id', // Foreign key on the missions table...
            'mission_id', // Local key on the mission_expenses table...
            'id' // Local key on the schools table...
        );
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
