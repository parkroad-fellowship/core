<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyAfricasTalkingWebhook
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request):Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $configuredSecret = config('prf.app.africas_talking.webhook_secret');

        if (! $configuredSecret) {
            abort(403, 'Webhook secret not configured.');
        }

        $providedSecret = $request->header('X-Webhook-Secret');

        if (! $providedSecret || ! hash_equals($configuredSecret, $providedSecret)) {
            abort(403, 'Invalid webhook secret.');
        }

        return $next($request);
    }
}
