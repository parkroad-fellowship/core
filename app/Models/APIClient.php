<?php

namespace App\Models;

use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class APIClient extends Model
{
    use HasFactory, HasUlid, SoftDeletes;

    protected $table = 'api_clients';

    protected $fillable = [
        'name',
        'app_id',
        'secret',
        'is_active',
        'allowed_roles',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'secret' => 'encrypted',
            'allowed_roles' => 'array',
        ];
    }

    /**
     * Check if this API client allows the given user based on their roles.
     * Empty allowed_roles means all roles are permitted.
     */
    public function allowsUser(User $user): bool
    {
        $allowedRoles = $this->allowed_roles ?? [];

        if (empty($allowedRoles)) {
            return true;
        }

        return $user->hasAnyRole($allowedRoles);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    protected static function booted(): void
    {
        static::saved(fn (APIClient $client) => self::clearSignatureCache($client->app_id));
        static::deleted(fn (APIClient $client) => self::clearSignatureCache($client->app_id));
    }

    public static function clearSignatureCache(?string $appId = null): void
    {
        Cache::forget('api_clients:exists');

        if ($appId) {
            Cache::forget("api_clients:app:{$appId}");
        }
    }
}
