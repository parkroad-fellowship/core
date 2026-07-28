<?php

namespace App\Models;

use App\Observers\TenantObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Models\Domain;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

#[ObservedBy(TenantObserver::class)]
class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase;
    use HasDomains;

    /** @use HasFactory<\Database\Factories\TenantFactory> */
    use HasFactory;

    protected $fillable = [
        'data',
        'id',
        'is_active',
        'name',
        'slug',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'data' => 'array',
        ];
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'tenant_user',
            'tenant_id',
            'user_id',
        )->withPivot('role')
            ->withTimestamps();
    }

    public function hasMember(User $user): bool
    {
        return $this->members()->whereKey($user->getKey())->exists();
    }

    public function addDomain(string $domain): Domain
    {
        return $this->domains()->firstOrCreate(['domain' => $domain]);
    }
}
