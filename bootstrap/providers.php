<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\CentralPanelProvider;
use App\Providers\Filament\TenantPanelProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\JetstreamServiceProvider;
use App\Providers\TelescopeServiceProvider;
use App\Providers\TenancyServiceProvider;

return [
    AppServiceProvider::class,
    CentralPanelProvider::class,
    TenantPanelProvider::class,
    FortifyServiceProvider::class,
    JetstreamServiceProvider::class,
    TelescopeServiceProvider::class,
    TenancyServiceProvider::class,
];
