<?php

namespace App\Models;

use App\Enums\PRFTransactionType;
use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class TransferRate extends Model
{
    use BelongsToTenant;
    use HasUlid;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'transaction_type',
        'min_amount',
        'max_amount',
        'charge',
    ];

    protected function casts(): array
    {
        return [
            'transaction_type' => PRFTransactionType::class,
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
