<?php

namespace App\Models;

use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class SmsLog extends Model
{
    use BelongsToTenant;
    use HasUlid;
    use SoftDeletes;

    protected $fillable = [
        'sms_loggable_id',
        'sms_loggable_type',
        'phone',
        'message',
        'message_id',
        'is_blacklisted',
        'response',
    ];

    protected $casts = [
        'response' => 'array',
        'is_blacklisted' => 'boolean',
    ];

    public function smsLoggable(): MorphTo
    {
        return $this->morphTo();
    }
}
