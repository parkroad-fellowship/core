<?php

namespace App\Filament\Resources\AppSettings\Pages;

use App\Enums\PRFIntegration;
use App\Filament\Resources\AppSettings\AppSettingResource;
use App\Services\Tenancy\TenantIntegrations;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAppSettings extends ListRecords
{
    protected static string $resource = AppSettingResource::class;

    /**
     * Tells admins which integrations still need their own credentials, and where Paystack must send webhooks.
     */
    public function getSubheading(): ?string
    {
        $missing = collect(app(TenantIntegrations::class)->unconfigured())
            ->map(fn(PRFIntegration $integration) => $integration->getLabel())
            ->join(', ');

        $webhookUrl = route('api.paystack.notifyPayment', ['tenant' => tenant('id')]);

        return (
            ($missing === '' ? 'All integrations are configured.' : "Not configured yet: {$missing}.")
            . " Paystack webhook URL: {$webhookUrl}"
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
