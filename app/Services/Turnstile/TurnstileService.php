<?php

namespace App\Services\Turnstile;

use App\Rules\Turnstile\ValidTurnstile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TurnstileService
{
    public const SITEVERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public function isEnabled(): bool
    {
        return (bool) config('services.turnstile.enabled', true);
    }

    /**
     * Validation rules for the Turnstile token field.
     *
     * When Turnstile is disabled (local/dev bypass), the field is nullable
     * and never fails with "required" — the service verify() also passes.
     *
     * @return array<int, mixed>
     */
    public function fieldRules(): array
    {
        if (!$this->isEnabled()) {
            return ['nullable', 'string', 'max:2048'];
        }

        return ['required', 'string', 'max:2048', new ValidTurnstile()];
    }

    /**
     * Friendly attribute name so errors read "security check", not the raw field.
     */
    public static function fieldName(): string
    {
        return 'cf-turnstile-response';
    }

    /**
     * Verify a Turnstile token against Cloudflare's siteverify API.
     *
     * @return array{success: bool, error: ?string, payload: array}
     */
    public function verify(string $token, ?string $remoteIp = null): array
    {
        $secret = (string) config('services.turnstile.secret');

        if (!$this->isEnabled()) {
            return ['success' => true, 'error' => null, 'payload' => []];
        }

        if ($secret === '') {
            Log::warning('Turnstile verification skipped: TURNSTILE_SECRET_KEY is not configured.');

            return ['success' => false, 'error' => 'turnstile-not-configured', 'payload' => []];
        }

        try {
            $response = Http::timeout(5)
                ->asForm()
                ->post(self::SITEVERIFY_URL, array_filter([
                    'secret' => $secret,
                    'response' => $token,
                    'remoteip' => $remoteIp,
                    'idempotency_key' => (string) Str::uuid(),
                ]));

            $payload = $response->json() ?? [];

            if ($payload['success'] ?? false) {
                if (!$this->hostnameMatches($payload['hostname'] ?? null)) {
                    return ['success' => false, 'error' => 'hostname-mismatch', 'payload' => $payload];
                }

                return ['success' => true, 'error' => null, 'payload' => $payload];
            }

            Log::info('Turnstile verification failed.', ['error-codes' => $payload['error-codes'] ?? []]);

            return [
                'success' => false,
                'error' => implode(',', (array) ($payload['error-codes'] ?? ['verification-failed'])),
                'payload' => $payload,
            ];
        } catch (\Throwable $th) {
            Log::warning('Turnstile siteverify request failed.', ['error' => $th->getMessage()]);

            return ['success' => false, 'error' => 'verification-unavailable', 'payload' => []];
        }
    }

    private function hostnameMatches(?string $hostname): bool
    {
        /** @var array<int, string> $expected */
        $expected = (array) config('services.turnstile.expected_hostnames', []);

        $legacy = strtolower(trim((string) config('services.turnstile.expected_hostname', '')));

        if ($legacy !== '' && !in_array($legacy, $expected, true)) {
            $expected[] = $legacy;
        }

        if ($expected === [] || $hostname === null) {
            return true;
        }

        return in_array(strtolower($hostname), $expected, true);
    }
}
