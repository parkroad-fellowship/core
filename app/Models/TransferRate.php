<?php

namespace App\Models;

use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class TransferRate extends Model
{
    use HasUlid;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'transaction_type',
        'min_amount',
        'max_amount',
        'charge',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
