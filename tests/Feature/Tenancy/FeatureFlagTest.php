<?php

use App\Enums\PRFFeature;
use App\Http\Middleware\CheckFeature;
use App\Models\AppSetting;
use Illuminate\Http\Request;

function checkFeature(string $feature): int
{
    return new CheckFeature()->handle(Request::create('/'), fn() => response('ok'), $feature)->getStatusCode();
}

it('keeps core features enabled even when switched off', function () {
    AppSetting::set('feature.missions', '0', 'features', 'boolean');

    expect(AppSetting::isFeatureEnabled(PRFFeature::MISSIONS))->toBeTrue();
});

it('blocks disabled features', function () {
    AppSetting::set('feature.events', '0', 'features', 'boolean');

    expect(checkFeature('events'))->toBe(403);
});

it('allows enabled features', function () {
    AppSetting::set('feature.events', '1', 'features', 'boolean');

    expect(checkFeature('events'))->toBe(200);
});

it('rejects unknown features', function () {
    expect(checkFeature('teleportation'))->toBe(422);
});
