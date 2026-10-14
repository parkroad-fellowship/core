<?php

namespace App\Services\Tenancy;

use App\Enums\PRFIntegration;
use App\Exceptions\IntegrationNotConfiguredException;
use App\Models\AppSetting;

/**
 * Loads each tenant's own integration credentials into config() and guards their use.
 *
 * There is no fallback to .env: values a tenant has not set are null, and
 * require() throws so the feature fails closed instead of using another account.
 */
class TenantIntegrations
{
    /**
     * Copy the current tenant's integration settings into config().
     */
    public function load(): void
    {
        foreach (PRFIntegration::cases() as $integration) {
            $defaults = $integration->defaults();

            foreach ($integration->settings() as $settingKey => $configKey) {
                $value = AppSetting::get($settingKey);

                config([$configKey => filled($value) ? $value : $defaults[$settingKey] ?? null]);
            }
        }
    }

    /**
     * Clear every tenant-owned value so nothing leaks into the next tenant or a central job.
     */
    public function reset(): void
    {
        foreach (PRFIntegration::cases() as $integration) {
            foreach ($integration->settings() as $configKey) {
                config([$configKey => null]);
            }
        }
    }

    public function isConfigured(PRFIntegration $integration): bool
    {
        return $this->missingSettings($integration) === [];
    }

    /**
     * @throws IntegrationNotConfiguredException
     */
    public function require(PRFIntegration $integration): void
    {
        $missing = $this->missingSettings($integration);

        if ($missing !== []) {
            throw new IntegrationNotConfiguredException($integration, $missing);
        }
    }

    /**
     * @return list<string>
     */
    public function missingSettings(PRFIntegration $integration): array
    {
        if (!tenancy()->initialized) {
            return $integration->requiredSettings();
        }

        $values = [];

        foreach ($integration->settings() as $settingKey => $configKey) {
            $values[$settingKey] = config($configKey);
        }

        return array_values(array_filter($integration->requiredSettings($values), fn(string $settingKey): bool => blank(
            $values[$settingKey] ?? null,
        )));
    }

    /**
     * @return list<PRFIntegration>
     */
    public function unconfigured(): array
    {
        return array_values(array_filter(
            PRFIntegration::cases(),
            fn(PRFIntegration $integration): bool => !$this->isConfigured($integration),
        ));
    }
}
