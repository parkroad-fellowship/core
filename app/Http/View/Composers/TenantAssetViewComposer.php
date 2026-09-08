<?php

namespace App\Http\View\Composers;

use App\Settings\TenantSettings;
use Illuminate\View\View;

class TenantAssetViewComposer
{
    public function compose(View $view): void
    {
        // Always share a value so queued/central renders (e.g. backup
        // notifications) never hit "Undefined variable $tenantSettings".
        // fromCentral() uses static defaults and never touches tenant storage.
        $view->with(
            'tenantSettings',
            tenancy()->initialized ? TenantSettings::fromCurrentTenant() : TenantSettings::fromCentral(),
        );
    }
}
