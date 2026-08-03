<?php

namespace App\Models;

use App\Helpers\Utils;
use App\Models\Concerns\HasConnectedAccounts;
use App\Models\Concerns\HasCrossDomainConnection;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasUlid;
use App\Models\Concerns\SetsProfilePhotoFromUrl;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    use HasApiTokens;
    use HasConnectedAccounts;
    use HasCrossDomainConnection;
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

    protected $fillable = [
        'ulid',
        'name',
        'email',
        'password',
        'timezone',
        'fcm_tokens',
        'is_desk_email',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    protected $appends = [
        'profile_photo_url',
    ];

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
        // Central panel: email-based access with bootstrap
        if ($panel->getId() === 'central') {
            $adminEmails = CentralSetting::getAdminEmails();

            // Bootstrap: if no admin emails configured yet, allow any authenticated user
            if ($adminEmails === [] || app()->isLocal()) {
                return true;
            }

            return in_array(strtolower($this->email), $adminEmails, true);
        }

        // Tenant panel: must belong to tenant
        if (tenancy()->initialized) {
            if (! $this->belongsToTenant(tenant('id'))) {
                return false;
            }

            // Super admins and users with org emails can access the panel
            if ($this->hasRole('super admin')) {
                return true;
            }

            $orgDomain = Utils::getOrgEmailDomain();

            return str_ends_with($this->email, '@'.$orgDomain);
        }

        // Fallback: super admin can access any panel
        return $this->hasRole('super admin');
    }

    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(
            Tenant::class,
            'tenant_user',
            'user_id',
            'tenant_id',
        )->withPivot('role')
            ->withTimestamps();
    }

    public function roles(): BelongsToMany
    {
        $relation = $this->morphToMany(
            config('permission.models.role'),
            'model',
            config('permission.table_names.model_has_roles'),
            config('permission.column_names.model_morph_key'),
            app(PermissionRegistrar::class)->pivotRole
        );

        if (! config('permission.teams')) {
            return $relation;
        }

        $teamForeignKey = config('permission.column_names.team_foreign_key');
        $teamField = config('permission.table_names.roles').'.'.$teamForeignKey;
        $teamId = getPermissionsTeamId();

        $relation = $relation->withPivot($teamForeignKey);

        if (! is_null($teamId)) {
            // Persist the team id on the pivot when writing roles so raw
            // sync/attach (e.g. Filament relationship selects) scope rows
            // to the current tenant instead of writing NULL tenant ids.
            $relation = $relation->withPivotValue($teamForeignKey, $teamId);
        } else {
            $relation = $relation->wherePivot($teamForeignKey, $teamId);
        }

        return $relation->where(fn ($query) => $query->whereNull($teamField)->orWhere($teamField, $teamId));
    }

    public function belongsToTenant(string $tenantId): bool
    {
        return $this->tenants()->where('tenants.id', $tenantId)->exists();
    }

    public function getTenantIdsAttribute(): array
    {
        return $this->tenants()->pluck('tenants.id')->toArray();
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
