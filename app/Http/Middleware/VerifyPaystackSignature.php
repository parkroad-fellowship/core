<?php

namespace App\Http\Middleware;

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
    public function handle(Request $request, Closure $next): Response
    {
        $signature = $request->header('X-Paystack-Signature');

        if (! $signature) {
            abort(403, 'Missing Paystack signature.');
        }

        $computed = hash_hmac('sha512', $request->getContent(), config('prf.payments.paystack.secret_key'));

        if (! hash_equals($computed, $signature)) {
            abort(403, 'Invalid Paystack signature.');
        }

        return $next($request);
    }
}
