<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class CentralSetting extends Model
{
    protected $table = 'central_settings';

    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
    ];

    private const CACHE_TTL = 3600;

    public static function getCacheKey(): string
    {
        return 'central_settings';
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $settings = Cache::remember(self::getCacheKey(), self::CACHE_TTL, function (): array {
            return self::query()
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
        $group = $group ?? explode('.', $key)[0] ?? 'general';

        $record = self::updateOrCreate(
            ['key' => $key],
            [
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

    /**
     * @return array<int, string>
     */
    public static function getAdminEmails(): array
    {
        $emails = self::get('admin_emails', []);

        if (is_string($emails)) {
            $emails = json_decode($emails, true) ?? [];
        }

        return array_map('strtolower', array_filter($emails));
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
