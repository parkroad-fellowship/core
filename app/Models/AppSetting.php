<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AppSetting extends Model
{
    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
    ];

    private const CACHE_KEY = 'app_settings';

    private const CACHE_TTL = 3600;

    protected static function booted(): void
    {
        static::saved(fn () => self::clearCache());
        static::deleted(fn () => self::clearCache());
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $settings = Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function (): array {
            return self::all()
                ->mapWithKeys(fn (self $setting) => [$setting->key => $setting->castValue()])
                ->toArray();
        });

        return $settings[$key] ?? $default;
    }

    public static function set(string $key, mixed $value, ?string $group = null, string $type = 'string'): self
    {
        $group = $group ?? explode('.', $key)[0] ?? 'general';

        $setting = self::updateOrCreate(
            ['key' => $key],
            [
                'group' => $group,
                'value' => is_array($value) ? json_encode($value) : (string) $value,
                'type' => $type,
            ]
        );

        self::clearCache();

        return $setting;
    }

    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
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
