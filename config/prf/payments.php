<?php

return [
    'paystack' => [
        'public_key' => env('PAYSTACK_PUBLIC_KEY'),
        'secret_key' => env('PAYSTACK_SECRET_KEY'),
        'base_url' => env('PAYSTACK_API_URL', 'https://api.paystack.co'),
        'callback_url' => env('PAYSTACK_CALLBACK_URL', 'https://app.parkroadfellowship.org/payments/success'),
    ],
];
