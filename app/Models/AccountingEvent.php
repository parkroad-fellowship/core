<?php

namespace App\Models;

use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountingEvent extends Model
{
    use HasUlid;
    use SoftDeletes;

    protected $fillable = [
        'accounting_eventable_id',
        'accounting_eventable_type',
        'name',
        'description',
        'due_date',
        'status',
        'requisition_desk',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function requisitions()
    {
        return $this->hasMany(Requisition::class);
    }

    public function accountingEventable()
    {
        return $this->morphTo();
    }
}
