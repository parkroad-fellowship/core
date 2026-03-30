<?php

namespace App\Models;

use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Student extends Model
{
    use HasFactory;
    use HasUlid;
    use LogsActivity;
    use Notifiable;
    use SoftDeletes;

    protected $fillable = [
        'ulid',
        'name',
        'user_id',
        'fcm_tokens',
    ];

    protected $appends = [
        'email',
    ];

    public function getEmailAttribute()
    {
        return $this->name.'@'.config('prf.app.org_email_domain', 'example.org');
    }

    public function studentEnquiries()
    {
        return $this->hasMany(StudentEnquiry::class);
    }

    public function user()
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

        $targetApp = $notification instanceof \App\Contracts\HasTargetApp
            ? $notification->targetApp($this)
            : null;

        return collect($this->fcm_tokens)
            ->when($targetApp, fn ($tokens) => $tokens->where('app', $targetApp->value))
            ->pluck('token')
            ->all();
    }
}
