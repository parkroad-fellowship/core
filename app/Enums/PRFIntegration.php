<?php

namespace App\Enums;

/**
 * Third-party integrations every tenant configures with its own credentials.
 *
 * Values are stored in the tenant's AppSettings and copied into config() when tenancy
 * starts. There is deliberately no fallback to .env: a tenant that has not configured
 * an integration cannot use it (see App\Services\Tenancy\TenantIntegrations).
 */
enum PRFIntegration: int
{
    case PAYSTACK = 1;
    case SMS = 2;
    case FCM = 3;
    case AI = 4;
    case GOOGLE_WORKSPACE = 5;

    public static function getOptions(): array
    {
        return collect(self::cases())->mapWithKeys(fn(self $case) => [$case->value => $case->getLabel()])->all();
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::PAYSTACK => 'Paystack payments',
            self::SMS => 'SMS',
            self::FCM => 'Push notifications (Firebase)',
            self::AI => 'AI assistant',
            self::GOOGLE_WORKSPACE => 'Google Workspace accounts',
        };
    }

    /**
     * Tenant setting key => config key it populates.
     *
     * @return array<string, string>
     */
    public function settings(): array
    {
        return match ($this) {
            self::PAYSTACK => [
                'payments.paystack_secret_key' => 'prf.payments.paystack.secret_key',
                'payments.paystack_public_key' => 'prf.payments.paystack.public_key',
                'payments.paystack_callback_url' => 'prf.payments.paystack.callback_url',
                'payments.paystack_currency' => 'prf.payments.paystack.currency',
            ],
            self::SMS => [
                'sms.driver' => 'prf.sms.default',
                'sms.advanta_base_url' => 'prf.sms.advanta.base_url',
                'sms.advanta_api_key' => 'prf.sms.advanta.api_key',
                'sms.advanta_partner_id' => 'prf.sms.advanta.partner_id',
                'sms.advanta_short_code' => 'prf.sms.advanta.short_code',
                'sms.africas_talking_username' => 'prf.sms.africas_talking.username',
                'sms.africas_talking_api_key' => 'prf.sms.africas_talking.api_key',
                'sms.africas_talking_from' => 'prf.sms.africas_talking.from',
            ],
            self::FCM => [
                'firebase.service_account_json' => 'prf.firebase.service_account_json',
                'firebase.database_url' => 'prf.firebase.database_url',
            ],
            self::AI => [
                'ai.provider' => 'prf.ai.provider',
                'ai.endpoint' => 'prf.ai.endpoint',
                'ai.model' => 'prf.ai.model',
                'ai.api_key' => 'prf.ai.api_key',
                'ai.max_output_tokens' => 'prf.ai.max_output_tokens',
            ],
            self::GOOGLE_WORKSPACE => [
                'google_workspace.service_account_json' => 'prf.google_workspace.service_account_json',
                'google_workspace.admin_subject' => 'prf.google_workspace.admin_subject',
                'google_workspace.org_unit_path' => 'prf.google_workspace.org_unit_path',
            ],
        };
    }

    /**
     * Settings that must be filled before the integration can be used.
     *
     * @param  array<string, mixed>  $values  current tenant values, used for driver-dependent requirements
     * @return list<string>
     */
    public function requiredSettings(array $values = []): array
    {
        return match ($this) {
            self::PAYSTACK => [
                'payments.paystack_secret_key',
                'payments.paystack_public_key',
                'payments.paystack_callback_url',
            ],
            self::SMS => match ($values['sms.driver'] ?? 'advanta') {
                'advanta' => [
                    'sms.driver',
                    'sms.advanta_base_url',
                    'sms.advanta_api_key',
                    'sms.advanta_partner_id',
                    'sms.advanta_short_code',
                ],
                'africas_talking' => ['sms.driver', 'sms.africas_talking_username', 'sms.africas_talking_api_key'],
                // Drivers registered with SMSManager::extend() validate their own settings.
                default => ['sms.driver'],
            },
            self::FCM => ['firebase.service_account_json'],
            self::AI => in_array(
                $values['ai.provider'] ?? 'azure-foundry',
                ['azure-foundry', 'azure', 'openai-compatible', 'ollama'],
                true,
            )
                    ? ['ai.provider', 'ai.endpoint', 'ai.model', 'ai.api_key']
                    : ['ai.provider', 'ai.model', 'ai.api_key'],
            self::GOOGLE_WORKSPACE => ['google_workspace.service_account_json', 'google_workspace.admin_subject'],
        };
    }

    /**
     * Settings holding credentials; stored encrypted and masked in the admin panel.
     *
     * @return list<string>
     */
    public function secretSettings(): array
    {
        return match ($this) {
            self::PAYSTACK => ['payments.paystack_secret_key'],
            self::SMS => ['sms.advanta_api_key', 'sms.africas_talking_api_key'],
            self::FCM => ['firebase.service_account_json'],
            self::AI => ['ai.api_key'],
            self::GOOGLE_WORKSPACE => ['google_workspace.service_account_json'],
        };
    }

    /**
     * Defaults applied when an optional (non-required) setting is blank. Never credentials.
     *
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        return match ($this) {
            self::PAYSTACK => ['payments.paystack_currency' => 'KES'],
            self::SMS => ['sms.driver' => 'advanta'],
            self::AI => ['ai.provider' => 'azure-foundry', 'ai.max_output_tokens' => 16384],
            self::GOOGLE_WORKSPACE => ['google_workspace.org_unit_path' => '/'],
            default => [],
        };
    }

    public static function isSecretSetting(string $key): bool
    {
        foreach (self::cases() as $integration) {
            if (in_array($key, $integration->secretSettings(), true)) {
                return true;
            }
        }

        return false;
    }
}
