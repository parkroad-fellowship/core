<?php

namespace App\Models;

use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SmsLog extends Model
{
    use HasUlid;
    use SoftDeletes;

    protected $fillable = [
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
}
