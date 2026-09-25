<?php

namespace App\Http\Middleware;

use App\Contracts\Services\PaymentGatewayInterface;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rejects Paystack webhooks that are not signed with the current tenant's secret key.
 * Runs after InitializeTenancyByPath, so the gateway already holds that tenant's credentials.
 */
class VerifyPaystackSignature
{
    public function __construct(
        private readonly PaymentGatewayInterface $payment,
    ) {}

    /**
     * @param  Closure(Request):Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->payment->verifyWebhook($request)) {
            abort(403, 'Invalid Paystack signature.');
        }

        return $next($request);
    }
}
