<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\Sanctum;
use Laravel\Sanctum\TransientToken;
use Symfony\Component\HttpFoundation\Response;

class ValidateTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = tenant('id');

        $tenant = Tenant::find($tenantId);

        if (! $tenant || ! $tenant->is_active) {
            Log::channel('tenant')->warning('Tenant validation failed - inactive or not found', [
                'header_value' => $request->header('X-Tenant'),
                'user' => $request->user()?->ulid,
                'reason' => 'Tenant not found or disabled',
                'path' => $request->path(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'message' => 'Tenant not found or disabled.',
                'code' => 'TENANT_INACTIVE',
            ], 403);
        }

        if ($user = $request->user()) {
            if (! $user->belongsToTenant($tenantId)) {
                Log::channel('tenant')->warning('Tenant validation failed - user membership mismatch', [
                    'header_value' => $request->header('X-Tenant'),
                    'user' => $request->user()?->ulid,
                    'reason' => 'User not a member of this tenant',
                    'path' => $request->path(),
                    'ip' => $request->ip(),
                ]);

                return response()->json([
                    'message' => 'User is not a member of this fellowship.',
                    'code' => 'TENANT_USER_MISMATCH',
                ], 403);
            }

            $token = $user->currentAccessToken();

            if ($token instanceof TransientToken && $bearerToken = $request->bearerToken()) {
                $model = Sanctum::$personalAccessTokenModel;
                $personalAccessToken = $model::findToken($bearerToken);

                if ($personalAccessToken && $personalAccessToken->tenant_id !== $tenantId) {
                    return response()->json([
                        'message' => 'Token is not valid for this fellowship.',
                        'code' => 'TENANT_TOKEN_MISMATCH',
                    ], 401);
                }
            } elseif ($token && ! $token instanceof TransientToken && $token->tenant_id !== $tenantId) {
                Log::channel('tenant')->warning('Tenant validation failed - token tenant mismatch', [
                    'header_value' => $request->header('X-Tenant'),
                    'user' => $user->ulid,
                    'reason' => 'Token not bound to this tenant',
                    'path' => $request->path(),
                    'ip' => $request->ip(),
                ]);

                return response()->json([
                    'message' => 'Token is not valid for this fellowship.',
                    'code' => 'TENANT_TOKEN_MISMATCH',
                ], 401);
            }
        }

        return $next($request);
    }
}
