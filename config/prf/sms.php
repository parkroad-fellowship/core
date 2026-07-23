<?php

return [
    'default' => env('SMS_PROVIDER', 'advanta'),
    'test_phone_number' => '+254703175638',
    'advanta' => [
        'base_url' => env('ADVANTA_BASE_URL'),
        'partner_id' => env('ADVANTA_PARTNER_ID'),
        'api_key' => env('ADVANTA_API_KEY'),
        'short_code' => env('ADVANTA_SHORT_CODE'),
    ],
    'africas_talking' => [
        'username' => env('AFRICAS_TALKING_USERNAME'),
        'api_key' => env('AFRICAS_TALKING_API_KEY'),
    ],
];
