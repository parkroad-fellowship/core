<?php

namespace App\Settings;

use App\Models\AppSetting;

readonly class TenantSettings
{
    /**
     * @param  array<string>  $enabledFeatures
     * @param  array<string, mixed>  $deskEmails
     */
    public function __construct(
        public string $organizationName,
        public string $logoURL,
        public string $faviconURL,
        public string $primaryColor,
        public array $enabledFeatures,
        public array $deskEmails,
    ) {}

    public static function fromCurrentTenant(): self
    {
        return new self(
            organizationName: (string) AppSetting::get('organization.name', config('app.name', 'Parkroad Fellowship')),
            logoURL: (string) AppSetting::get('organization.logo_url', '/images/default-logo.png'),
            faviconURL: (string) AppSetting::get('organization.favicon_url', '/favicon.ico'),
            primaryColor: (string) AppSetting::get('branding.primary_color', '#1E40AF'),
            enabledFeatures: (array) AppSetting::get('features.list', []),
            deskEmails: (array) AppSetting::get('desk_emails.map', []),
        );
    }
}
