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
        'expensable_id',
        'expensable_type',
        'amount',
        'charge',
        'confirmation_message',
    ];

    protected $casts = [
        'amount' => 'integer',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function expensable()
    {
        return $this->morphTo();
    }
}
