<?php

namespace App\Http\Middleware;

use App\Models\Payment;
use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ResolveTenantFromWebhookPayload
{
    public function handle(Request $request, Closure $next): Response
    {
        $reference = $request->input('data.reference');

        abort_if(blank($reference), 422, 'Missing provider reference.');

        $payment = Payment::query()->where('provider_reference', $reference)->firstOrFail();
        $tenant = Tenant::query()->findOrFail($payment->tenant_id);

        tenancy()->initialize($tenant);

        try {
            return $next($request);
        } finally {
            tenancy()->end();
        }
    }
}
