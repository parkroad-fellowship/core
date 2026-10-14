<?php

namespace App\Filament\Resources\AppSettings\Pages;

use App\Enums\PRFIntegration;
use App\Filament\Resources\AppSettings\AppSettingResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAppSetting extends EditRecord
{
    protected static string $resource = AppSettingResource::class;

    /**
     * Secrets are never sent back to the browser.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (PRFIntegration::isSecretSetting((string) ($data['key'] ?? ''))) {
            $data['value'] = null;
        }

        return $data;
    }

    /**
     * A blank secret keeps the stored value.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (PRFIntegration::isSecretSetting((string) ($data['key'] ?? '')) && blank($data['value'] ?? null)) {
            unset($data['value']);
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
