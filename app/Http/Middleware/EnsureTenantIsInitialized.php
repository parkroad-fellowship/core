<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedByRequestDataException;
use Stancl\Tenancy\Resolvers\RequestDataTenantResolver;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantIsInitialized
{
    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header(RequestDataTenantResolver::headerName());

        if (blank($header)) {
            return response()->json([
                'message' => 'X-Tenant header is required.',
                'code' => 'TENANT_REQUIRED',
            ], 422);
        }

        if (!Str::isUlid($header)) {
            return response()->json([
                'message' => 'Invalid tenant identifier format.',
                'code' => 'INVALID_TENANT_FORMAT',
            ], 422);
        }

        if (!tenancy()->initialized) {
            try {
                $resolver = app(RequestDataTenantResolver::class);
                $tenant = $resolver->resolve($header);
                tenancy()->initialize($tenant);
            } catch (TenantCouldNotBeIdentifiedByRequestDataException $e) {
                return response()->json([
                    'message' => 'Tenant not found.',
                    'code' => 'TENANT_NOT_FOUND',
                ], 404);
            }
        }

        return $next($request);
    }
}
