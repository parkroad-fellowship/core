<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Enums\PRFRole;
use App\Models\Concerns\HasConnectedAccounts;
use App\Models\Concerns\HasCrossDomainConnection;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasULID;
use App\Models\Concerns\SetsProfilePhotoFromURL;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
use Spatie\QueryBuilder\AllowedFilter;

#[Fillable([
    'ulid',
    'name',
    'email',
    'password',
    'timezone',
    'fcm_tokens',
    'is_desk_email',
])]
#[Hidden([
    'password',
    'remember_token',
    'two_factor_recovery_codes',
    'two_factor_secret',
])]
#[Appends([
    'profile_photo_url',
])]
class User extends Authenticatable implements FilamentUser, MustVerifyEmail, HasQueryBuilderCapabilities
{
    use HasApiTokens;
    use HasConnectedAccounts;
    use HasCrossDomainConnection;
    /** @use HasFactory<UserFactory> */
    use HasFactory;
    use HasModelPermissions;
    use HasProfilePhoto {
        HasProfilePhoto::profilePhotoUrl as getPhotoUrl;
    }
    use HasRoles;
    use HasULID;
    use LogsActivity;
    use Notifiable;
    use SetsProfilePhotoFromURL;
    use SoftDeletes;
    use TwoFactorAuthenticatable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'fcm_tokens' => 'array',
        ];
    }

    public const INCLUDES = [
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

    public const SORTS = ['created_at', 'updated_at'];

    /**
     * @return array<int, AllowedFilter>
     */
    public static function filters(): array
    {
        return [];
    }

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
            if (!$this->belongsToTenant(tenant('id'))) {
                return false;
            }

            // Leadership and desk roles carry this permission; plain members and students do not.
            return $this->hasRole(PRFRole::SUPER_ADMIN) || $this->can('access tenant panel');
        }

        // Fallback: super admin can access any panel
        return $this->hasRole(PRFRole::SUPER_ADMIN);
    }

    public function tenants(): BelongsToMany
    {
        return $this
            ->belongsToMany(Tenant::class, 'tenant_user', 'user_id', 'tenant_id')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function roles(): BelongsToMany
    {
        $relation = $this->morphToMany(
            config('permission.models.role'),
            'model',
            config('permission.table_names.model_has_roles'),
            config('permission.column_names.model_morph_key'),
            app(PermissionRegistrar::class)->pivotRole,
        );

        if (!config('permission.teams')) {
            return $relation;
        }

        $teamForeignKey = config('permission.column_names.team_foreign_key');
        $teamField = config('permission.table_names.roles') . '.' . $teamForeignKey;
        $teamId = getPermissionsTeamId();

        $relation = $relation->withPivot($teamForeignKey);

        if (!is_null($teamId)) {
            // Persist the team id on the pivot when writing roles so raw
            // sync/attach (e.g. Filament relationship selects) scope rows
            // to the current tenant instead of writing NULL tenant ids.
            $relation = $relation->withPivotValue($teamForeignKey, $teamId);
        } else {
            $relation = $relation->wherePivot($teamForeignKey, $teamId);
        }

        return $relation->where(fn($query) => $query->whereNull($teamField)->orWhere($teamField, $teamId));
    }

    public function belongsToTenant(string $tenantId): bool
    {
        return $this->tenants()->where('tenants.id', $tenantId)->exists();
    }

    public function getTenantIdsAttribute(): array
    {
        return $this->tenants()->pluck('tenants.id')->toArray();
    }

    /**
     * @return HasOne<Member, $this>
     */
    public function member(): HasOne
    {
        return $this->hasOne(Member::class);
    }

    /**
     * @return HasOne<Student, $this>
     */
    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    /**
     * @return HasManyThrough<GroupMember, $this>
     */
    public function groupMembers(): HasManyThrough
    {
        return $this->hasManyThrough(related: GroupMember::class, through: Member::class);
    }

    public function profilePhotoUrl(): Attribute
    {
        return filter_var($this->profile_photo_path, FILTER_VALIDATE_URL)
            ? Attribute::get(fn() => $this->profile_photo_path)
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

        $targetApp = $notification instanceof \App\Contracts\HasTargetApp ? $notification->targetApp($this) : null;

        return collect($this->fcm_tokens)
            ->when($targetApp, fn($tokens) => $tokens->where('app', $targetApp->value))
            ->pluck('token')
            ->all();
    }
}
