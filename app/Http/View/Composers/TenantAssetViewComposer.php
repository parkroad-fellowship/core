<?php

namespace App\Http\View\Composers;

use App\Settings\TenantSettings;
use Illuminate\View\View;

class TenantAssetViewComposer
{
    public function compose(View $view): void
    {
        if (tenancy()->initialized) {
            $view->with('tenantSettings', TenantSettings::fromCurrentTenant());
        }
    }
}
