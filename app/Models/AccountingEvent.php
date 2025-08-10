<?php

namespace App\Models;

use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AccountingEvent extends Model
{
    use HasUlid;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'accounting_eventable_id',
        'accounting_eventable_type',
        'name',
        'description',
        'due_date',
        'status',
        'responsible_desk',
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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
