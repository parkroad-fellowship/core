<?php

namespace App\Models;

use App\Models\Concerns\HasCrossDomainConnection;
use App\Models\Concerns\HasModelPermissions;
use Database\Factories\ConnectedAccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'provider',
    'provider_id',
    'name',
    'nickname',
    'email',
    'avatar_path',
    'token',
    'secret',
    'refresh_token',
    'expires_at',
])]
class ConnectedAccount extends Model
{
    use HasCrossDomainConnection;
    /** @use HasFactory<ConnectedAccountFactory> */
    use HasFactory;
    use HasModelPermissions;
    use HasTimestamps;
    use LogsActivity;

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
