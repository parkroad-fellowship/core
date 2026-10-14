<?php

/*
 | SMS credentials are owned by each tenant (App Settings → SMS) and loaded by
 | App\Services\Tenancy\TenantIntegrations. There is no .env fallback.
 */
return [
    'default' => 'advanta',
    'region' => env('SMS_PHONE_REGION', 'KE'),
    'timeout' => (int) env('SMS_TIMEOUT', 15),
    // Outside production every SMS is redirected here instead of the real recipient.
    'test_phone_number' => env('SMS_TEST_PHONE_NUMBER'),
    'advanta' => [
        'base_url' => null,
        'partner_id' => null,
        'api_key' => null,
        'short_code' => null,
    ],
    'africas_talking' => [
        'username' => null,
        'api_key' => null,
        'from' => null,
    ],
];
