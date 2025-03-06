<?php

return [
    'gemini' => [
        'model' => 'gemini-2.0-flash',
        'api_key' => env('GEMINI_API_KEY'),
        'max_output_tokens' => env('GEMINI_MAX_OUTPUT_TOKENS', 8192),
    ],
    'google_maps' => [
        'api_key' => env('GOOGLE_MAPS_API_KEY'),
    ],
    'head_office' => [
        'latitude' => env('HEAD_OFFICE_LATITUDE', '-1.2906674'),
        'longitude' => env('HEAD_OFFICE_LONGITUDE', '36.7690094'),
    ],
    'azure_speech' => [
        'subscription_key' => env('AZURE_SPEECH_SUBSCRIPTION_KEY'),
        'region' => env('AZURE_SPEECH_REGION', 'southafricanorth'),
    ],
    'africas_talking' => [
        'username' => env('AFRICAS_TALKING_USERNAME'),
        'api_key' => env('AFRICAS_TALKING_API_KEY'),
        'callback_url' => env('APP_URL', 'https://hwy5en3pj6.sharedwithexpose.com'),
        'from' => env('AFRICAS_TALKING_FROM', '+254711082571'),
        'missions_desk' => env('PRF_MISSIONS_DESK', 'agent.enquiries@ke.sip.africastalking.com	'),
    ]
];
