<?php

return [
    'pesapal' => [
        'api_url' => env('PESAPAL_API_URL', 'https://cybqa.pesapal.com/pesapalv3/api'),
        'consumer_key' => env('PESAPAL_CONSUMER_KEY', 'qkio1BGGYAXTu2JOfm7XSXNruoZsrqEW'),
        'consumer_secret' => env('PESAPAL_CONSUMER_SECRET', 'osGQ364R49cXKeOYSpaOnT++rHs='),
        'callback_url' => env('PESAPAL_CALLBACK_URL', 'https://prf.app/cool-beans'),
        'notification_id' => env('PESAPAL_NOTIFICATION_ID', 'a1a6268a-4670-4045-bb8b-e03f928627ec'),
    ],
];
