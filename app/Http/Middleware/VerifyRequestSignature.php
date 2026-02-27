<?php

namespace App\Http\Middleware;

use App\Helpers\RequestSigner;
use App\Models\APIClient;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class VerifyRequestSignature
{
    private const CACHE_TTL_SECONDS = 60;

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->hasAPIClients()) {
            return $next($request);
        }

        $signature = $request->header('X-PRF-Signature');
        $timestamp = $request->header('X-PRF-Timestamp');
        $appId = $request->header('X-PRF-App-ID');

        if (! $signature || ! $timestamp || ! $appId) {
            return response()->json([
                'error' => 'Missing required signature headers',
                'message' => 'X-PRF-Signature, X-PRF-Timestamp, and X-PRF-App-ID headers are required',
            ], 401);
        }

        if (! $this->isValidTimestamp($timestamp)) {
            return response()->json([
                'error' => 'Invalid timestamp',
                'message' => 'Request timestamp is too old or invalid',
            ], 401);
        }

        if (! $this->verifySignature($request, $signature, $timestamp, $appId)) {
            return response()->json([
                'error' => 'Invalid signature',
                'message' => 'Request signature verification failed',
            ], 401);
        }

        $requestId = (string) Str::uuid();
        Context::add('request_id', $requestId);

        /** @var Response $response */
        $response = $next($request);
        $response->headers->set('X-Request-ID', $requestId);

        return $response;
    }

    private function hasAPIClients(): bool
    {
        return Cache::remember('api_clients:exists', self::CACHE_TTL_SECONDS, function (): bool {
            return APIClient::query()->exists();
        });
    }

    private function isValidTimestamp(string $timestamp): bool
    {
        try {
            $requestTime = Carbon::createFromTimestampMs((int) $timestamp, 'UTC');
            $diffInSeconds = Carbon::now('UTC')->diffInSeconds($requestTime, absolute: false);

            return abs($diffInSeconds) <= 30;
        } catch (\Exception) {
            return false;
        }
    }

    private function verifySignature(Request $request, string $signature, string $timestamp, string $appId): bool
    {
        $client = Cache::remember("api_clients:app:{$appId}", self::CACHE_TTL_SECONDS, function () use ($appId): ?APIClient {
            return APIClient::query()
                ->active()
                ->where('app_id', $appId)
                ->first();
        });

        if (! $client) {
            return false;
        }

        // Laravel's ->fullUrl() sorts the query params alphabetically,
        // but we need the original order for signature verification
        $rawUrl = $request->getSchemeAndHttpHost().$request->getRequestUri();

        $expectedSignature = RequestSigner::generateSignature(
            $request->method(),
            $rawUrl,
            $timestamp,
            $appId,
            $client->secret
        );

        return hash_equals($expectedSignature, $signature);
    }
}
