<?php

return [
    'pesapal' => [
        'api_url' => env('PESAPAL_API_URL', 'https://cybqa.pesapal.com/pesapalv3/api'),
        'consumer_key' => env('PESAPAL_CONSUMER_KEY', 'qkio1BGGYAXTu2JOfm7XSXNruoZsrqEW'),
        'consumer_secret' => env('PESAPAL_CONSUMER_SECRET', 'osGQ364R49cXKeOYSpaOnT++rHs='),
        'callback_url' => env('PESAPAL_CALLBACK_URL', 'https://app.parkroadfellowship.org/payments/success'),
        'notification_id' => env('PESAPAL_NOTIFICATION_ID', '58f3d973-f7bf-4f6e-b785-dc2dc86fc888'),
    ],

    'paystack' => [
        'public_key' => env('PAYSTACK_PUBLIC_KEY'),
        'secret_key' => env('PAYSTACK_SECRET_KEY'),
        'base_url' => env('PAYSTACK_API_URL', 'https://api.paystack.co'),
    ],
];
