<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Initializes tenancy for the public pledge endpoint so that a pledge gets a
 * tenant_id and shows up in the correct Treasurer panel. Central-domain
 * requests (the public pledge page) otherwise pass through without tenancy,
 * so this middleware assigns the configured PRF tenant explicitly.
 */
class ResolvePledgeTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        if (tenancy()->initialized) {
            return $next($request);
        }

        $tenant = Tenant::query()
            ->where('is_active', true)
            ->whereHas('domains', fn($q) => $q->whereIn('domain', config('prf.giving.pledge_tenant_domains', [
                'app.parkroadfellowship.org',
            ])))
            ->first() ?? Tenant::query()->where('is_active', true)->first();

        abort_if(!$tenant, 500, 'No tenant configured for pledges.');

        tenancy()->initialize($tenant);

        try {
            return $next($request);
        } finally {
            tenancy()->end();
        }
    }
}
