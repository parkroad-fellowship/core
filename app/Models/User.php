<?php

namespace App\Models;

use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasUlid;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use JoelButcher\Socialstream\HasConnectedAccounts;
use JoelButcher\Socialstream\SetsProfilePhotoFromUrl;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    use HasApiTokens;
    use HasConnectedAccounts;
    use HasFactory;
    use HasModelPermissions;
    use HasProfilePhoto {
        HasProfilePhoto::profilePhotoUrl as getPhotoUrl;
    }
    use HasRoles;
    use HasUlid;
    use LogsActivity;
    use Notifiable;
    use SetsProfilePhotoFromUrl;
    use SoftDeletes;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ulid',
        'name',
        'email',
        'password',
        'timezone',
        'fcm_tokens',
        'is_desk_email',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'fcm_tokens' => 'array',
        ];
    }

    const INCLUDES = [
        'roles',
        'roles.permissions',
        'member',
        'member.groupMembers',
        'member.groupMembers.group',
        'member.memberships',
        'member.memberships.spiritualYear',
        'member.profilePicture',
        'student',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        return str_ends_with($this->email, '@'.config('prf.app.org_email_domain', 'example.org')) && $this->hasVerifiedEmail();
    }

    public function member()
    {
        return $this->hasOne(Member::class);
    }

    public function student()
    {
        return $this->hasOne(Student::class);
    }

    public function groupMembers()
    {
        return $this->hasManyThrough(
            related: GroupMember::class,
            through: Member::class,
        );
    }

    /**
     * Get the URL to the user's profile photo.
     */
    public function profilePhotoUrl(): Attribute
    {
        return filter_var($this->profile_photo_path, FILTER_VALIDATE_URL)
            ? Attribute::get(fn () => $this->profile_photo_path)
            : $this->getPhotoUrl();
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
