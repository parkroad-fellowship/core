<?php

namespace App\Models;

use App\Enums\PRFSMSStatus;
use App\Models\Concerns\HasULID;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

#[Table('sms_logs')]
#[Fillable([
    'sms_loggable_id',
    'sms_loggable_type',
    'provider',
    'status',
    'cost',
    'delivered_at',
    'phone',
    'message',
    'message_id',
    'is_blacklisted',
    'response',
])]
class SMSLog extends Model
{
    use BelongsToTenant;
    use HasULID;
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'status' => PRFSMSStatus::class,
            'response' => 'array',
            'is_blacklisted' => 'boolean',
            'delivered_at' => 'datetime',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function smsLoggable(): MorphTo
    {
        return $this->morphTo();
    }
}
