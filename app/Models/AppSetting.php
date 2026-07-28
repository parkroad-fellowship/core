<?php

namespace App\Models;

use App\Enums\PRFFeature;
use App\Observers\AppSettingObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

#[ObservedBy(AppSettingObserver::class)]
class AppSetting extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'group',
        'key',
        'value',
        'type',
    ];

    private const CACHE_TTL = 3600;

    public static function getCacheKey(): string
    {
        $tenantId = tenancy()->initialized ? tenant('id') : 'central';

        return "app_settings_{$tenantId}";
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        if (! tenancy()->initialized) {
            return $default;
        }

        $tenantId = tenant('id');

        $settings = Cache::remember(self::getCacheKey(), self::CACHE_TTL, function () use ($tenantId): array {
            return self::query()
                ->where('tenant_id', $tenantId)
                ->get()
                ->mapWithKeys(fn (self $setting) => [$setting->key => $setting->castValue()])
                ->toArray();
        });

        return $settings[$key] ?? $default;
    }

    public static function set(
        string $key,
        mixed $value,
        ?string $group = null,
        string $type = 'string'
    ): self {
        if (! tenancy()->initialized) {
            throw new \RuntimeException('Refusing to write AppSetting outside tenant context.');
        }

        $group = $group ?? explode('.', $key)[0] ?? 'general';

        $record = self::updateOrCreate(
            ['tenant_id' => tenant('id'), 'key' => $key],
            [
                'tenant_id' => tenant('id'),
                'group' => $group,
                'key' => $key,
                'value' => is_scalar($value) ? (string) $value : json_encode($value),
                'type' => $type,
            ]
        );

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
        return match ($this->type) {
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $this->value,
            'array' => json_decode($this->value, true) ?? [],
            default => $this->value,
        };
    }
}
