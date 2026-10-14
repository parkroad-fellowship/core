<?php

/*
 | Paystack credentials are owned by each tenant (App Settings → Payments) and loaded by
 | App\Services\Tenancy\TenantIntegrations. There is no .env fallback.
 */
return [
    'paystack' => [
        'base_url' => env('PAYSTACK_API_URL', 'https://api.paystack.co'),
        'public_key' => null,
        'secret_key' => null,
        'callback_url' => null,
        'currency' => 'KES',
    ],
];
