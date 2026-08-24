<?php

namespace App\Http\Middleware;

use App\Contracts\Services\PaymentGatewayInterface;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyPaystackSignature
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request):Response  $next
     */
    public function handle(Request $request, Closure $next, PaymentGatewayInterface $payment): Response
    {
        if (!$payment->verifyWebhook($request)) {
            abort(403, 'Invalid Paystack signature.');
        }

        return $next($request);
    }
}
