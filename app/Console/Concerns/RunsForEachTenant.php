<?php

namespace App\Console\Concerns;

use App\Models\Tenant;

/**
 * Scheduled commands run without a tenant. Anything touching tenant data must run inside
 * each tenant so the tenant scope, RLS and that tenant's integration keys apply.
 */
trait RunsForEachTenant
{
    /**
     * @param  callable(Tenant): void  $callback
     */
    protected function forEachTenant(callable $callback, bool $activeOnly = true): void
    {
        Tenant::query()
            ->lazyById()
            ->filter(fn(Tenant $tenant): bool => !$activeOnly || $tenant->is_active !== false)
            ->each(function (Tenant $tenant) use ($callback): void {
                tenancy()->initialize($tenant);

                try {
                    $callback($tenant);
                } finally {
                    tenancy()->end();
                }
            });
    }
}
