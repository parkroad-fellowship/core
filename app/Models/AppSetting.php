<?php

namespace App\Models;

use App\Enums\PRFFeature;
use App\Enums\PRFIntegration;
use App\Models\Concerns\HasModelPermissions;
use App\Observers\AppSettingObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

#[Fillable([
    'tenant_id',
    'group',
    'key',
    'value',
    'type',
])]
#[ObservedBy(AppSettingObserver::class)]
class AppSetting extends Model
{
    use BelongsToTenant;
    use HasModelPermissions;

    private const CACHE_TTL = 3600;

    public const ENCRYPTED_PREFIX = 'enc:';

    public static function getCacheKey(): string
    {
        $tenantId = tenancy()->initialized ? tenant('id') : 'central';

        return "app_settings_{$tenantId}";
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        if (!tenancy()->initialized) {
            return $default;
        }

        $tenantId = tenant('id');

        // Cache the stored (still encrypted) values; secrets are only decrypted on read.
        $settings = Cache::remember(self::getCacheKey(), self::CACHE_TTL, function () use ($tenantId): array {
            return self::query()
                ->where('tenant_id', $tenantId)
                ->get()
                ->mapWithKeys(fn(self $setting) => [
                    $setting->key => ['type' => $setting->type, 'value' => $setting->value],
                ])
                ->toArray();
        });

        if (!array_key_exists($key, $settings)) {
            return $default;
        }

        return self::castStoredValue($settings[$key]['type'], $settings[$key]['value']) ?? $default;
    }

    public static function set(string $key, mixed $value, ?string $group = null, string $type = 'string'): self
    {
        if (!tenancy()->initialized) {
            throw new \RuntimeException('Refusing to write AppSetting outside tenant context.');
        }

        $group = $group ?? explode('.', $key)[0] ?? 'general';

        $record = self::updateOrCreate(['tenant_id' => tenant('id'), 'key' => $key], [
            'tenant_id' => tenant('id'),
            'group' => $group,
            'key' => $key,
            'value' => is_scalar($value) ? (string) $value : json_encode($value),
            'type' => $type,
        ]);

        self::clearCache();

        return $record;
    }

    public static function clearCache(): void
    {
        Cache::forget(self::getCacheKey());
    }

    public static function isFeatureEnabled(PRFFeature $feature): bool
    {
        if (in_array($feature, PRFFeature::core(), true)) {
            return true;
        }

        return (bool) self::get("feature.{$feature->value}", false);
    }

    public function castValue(): mixed
    {
        return self::castStoredValue($this->type, $this->value);
    }

    public function isSecret(): bool
    {
        return PRFIntegration::isSecretSetting($this->key);
    }

    /**
     * Encrypt the value of secret settings before it is written. Idempotent.
     */
    public function encryptSecretValue(): void
    {
        if ($this->isSecret() && filled($this->value) && !str_starts_with($this->value, self::ENCRYPTED_PREFIX)) {
            $this->value = self::ENCRYPTED_PREFIX . Crypt::encryptString($this->value);
        }
    }

    public static function castStoredValue(?string $type, ?string $value): mixed
    {
        if ($value !== null && str_starts_with($value, self::ENCRYPTED_PREFIX)) {
            $value = Crypt::decryptString(substr($value, strlen(self::ENCRYPTED_PREFIX)));
        }

        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'array' => json_decode($value ?? '', true) ?? [],
            default => $value,
        };
    }
}
