<?php

namespace App\Models;

use App\Helpers\Utils;
use App\Models\Concerns\HasULID;
use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

#[Fillable([
    'ulid',
    'name',
    'user_id',
    'fcm_tokens',
])]
#[Appends([
    'email',
])]
class Student extends Model
{
    use BelongsToTenant;
    /** @use HasFactory<StudentFactory> */
    use HasFactory;
    use HasULID;
    use LogsActivity;
    use Notifiable;
    use SoftDeletes;

    /**
     * Students sign in with a synthetic address. Existing students keep the one on their user;
     * new ones use the organisation domain, or the reserved `.invalid` TLD (RFC 2606) when the
     * tenant has none, so the address can never reach a real inbox.
     */
    public function getEmailAttribute(): string
    {
        return $this->user?->email ?? $this->name . '@' . (Utils::getOrgEmailDomain() ?? 'students.invalid');
    }

    /**
     * @return HasMany<StudentEnquiry, $this>
     */
    public function studentEnquiries(): HasMany
    {
        return $this->hasMany(StudentEnquiry::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

    public function routeNotificationForFcm($notification = null): array
    {
        if (empty($this->fcm_tokens)) {
            return [];
        }

        $targetApp = $notification instanceof \App\Contracts\HasTargetApp ? $notification->targetApp($this) : null;

        return collect($this->fcm_tokens)
            ->when($targetApp, fn($tokens) => $tokens->where('app', $targetApp->value))
            ->pluck('token')
            ->all();
    }
}
