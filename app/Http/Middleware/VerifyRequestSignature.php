<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class VerifyRequestSignature
{
    public function handle(Request $request, Closure $next): Response|JsonResponse
    {
        // Skip verification for certain routes (like auth endpoints)
        if ($this->shouldSkipVerification($request)) {
            return $next($request);
        }

        $signature = $request->header('X-Signature');
        $timestamp = $request->header('X-Timestamp');
        $appId = $request->header('X-App-ID');

        if (! $signature || ! $timestamp || ! $appId) {
            return response()->json([
                'error' => 'Missing required signature headers',
                'message' => 'X-Signature, X-Timestamp, and X-App-ID headers are required',
            ], 401);
        }

        // Check timestamp to prevent replay attacks (5 minute window)
        if (! $this->isValidTimestamp($timestamp)) {
            return response()->json([
                'error' => 'Invalid timestamp',
                'message' => 'Request timestamp is too old or invalid',
            ], 401);
        }

        // Verify the signature
        if (! $this->verifySignature($request, $signature, $timestamp, $appId)) {
            return response()->json([
                'error' => 'Invalid signature',
                'message' => 'Request signature verification failed',
            ], 401);
        }

        return $next($request);
    }

    private function shouldSkipVerification(Request $request): bool
    {
        $skipRoutes = [
            'api/v1/auth/login',
            'api/v1/auth/register',
            'api/v1/auth/register-student',
            'api/v1/auth/social-login',
            'api/v1/auth/social-leader-login',
        ];

        return in_array($request->path(), $skipRoutes);
    }

    private function isValidTimestamp(string $timestamp): bool
    {
        try {
            $requestTime = Carbon::createFromTimestamp($timestamp);
            $now = Carbon::now();

            // Allow 5 minute window
            return $requestTime->diffInMinutes($now) <= 5;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function verifySignature(Request $request, string $signature, string $timestamp, string $appId): bool
    {
        // Get the app secret for this app ID
        $appSecret = $this->getAppSecret($appId);
        if (! $appSecret) {
            return false;
        }

        // Create the expected signature
        $expectedSignature = $this->generateSignature($request, $timestamp, $appId, $appSecret);

        // Use hash_equals to prevent timing attacks
        return hash_equals($expectedSignature, $signature);
    }

    private function getAppSecret(string $appId): ?string
    {
        // You can store app secrets in database or config
        $appSecrets = config('prf.app.api_secrets', []);

        return $appSecrets[$appId] ?? null;
    }

    private function generateSignature(Request $request, string $timestamp, string $appId, string $appSecret): string
    {
        // Create signature string from: method + url + body + timestamp + appId
        $method = strtoupper($request->method());
        $url = $request->fullUrl();
        $body = $request->getContent();

        $stringToSign = $method.'|'.$url.'|'.$body.'|'.$timestamp.'|'.$appId;

        // Generate HMAC signature
        return hash_hmac('sha256', $stringToSign, $appSecret);
    }
}
