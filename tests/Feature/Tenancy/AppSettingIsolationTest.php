<?php

use App\Models\AppSetting;

beforeEach(function () {
    tenancy()->end();
});

it('isolates AppSetting by tenant', function () {
    $a = createTenant();
    initTenancy($a);
    AppSetting::set('test.a', 'v-a', 'tests', 'string');

    $b = createTenant();
    initTenancy($b);
    AppSetting::set('test.b', 'v-b', 'tests', 'string');

    initTenancy($a);
    expect(AppSetting::get('test.a'))->toBe('v-a');
});
