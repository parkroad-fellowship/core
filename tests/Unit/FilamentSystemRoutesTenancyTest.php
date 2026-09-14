<?php

use Stancl\Tenancy\Middleware\InitializeTenancyByDomainOrSubdomain;

it('initializes tenancy for filament system routes', function () {
    // Export/import downloads run outside panels with only the
    // `filament.actions` group. Without tenancy identification the
    // tenant-suffixed storage path is never applied and downloads 404.
    $group = app('router')->getMiddlewareGroups()['filament.actions'] ?? [];

    expect($group)->toContain(InitializeTenancyByDomainOrSubdomain::class);
});
