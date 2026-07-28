<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ConditionalTenancyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Conditionally initializes tenancy for Livewire routes.
     * Central domain requests pass through without tenancy.
     * Tenant domain requests have tenancy initialized directly.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $centralDomains = config('tenancy.identification.central_domains', []);
        $isCentral = in_array($request->getHost(), $centralDomains, true);

        if ($isCentral || tenancy()->initialized) {
            return $next($request);
        }

        $tenant = \App\Models\Tenant::query()
            ->whereHas('domains', fn ($q) => $q->where('domain', $request->getHost()))
            ->first();

        if ($tenant) {
            tenancy()->initialize($tenant);
        }

        return $next($request);
    }
}
