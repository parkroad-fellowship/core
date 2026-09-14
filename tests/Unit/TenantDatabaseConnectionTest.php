<?php

use Illuminate\Support\Facades\DB;

it('always defines the tenant database connection', function () {
    // Queued jobs (e.g. Filament exports) restore models serialized with a
    // 'tenant' connection in processes where tenancy was never initialized
    // or was already reverted. The connection must resolve regardless.
    $config = config('database.connections.tenant');

    expect($config)->toBeArray()->and($config['driver'])->toBe('pgsql');
});

it('resolves the tenant connection to the central database', function () {
    expect(DB::connection('tenant')->getDatabaseName())->toBe(config('database.connections.pgsql.database'));
});
