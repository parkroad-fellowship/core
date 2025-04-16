<?php

return [
    'global_group' => 'All',
    'excluded_emails' => [
        'approvals@parkroadfellowship.org',
    ],
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
        'callback_url' => env('AFRICAS_TALKING_CALLBACK_URL', 'https://app.parkroadfellowship.org'),
        'from' => env('AFRICAS_TALKING_FROM', '+254711082571'),
        'missions_desk' => env('PRF_MISSIONS_DESK', 'agent.enquiries@ke.sip.africastalking.com'),
        'os_desk' => env('PRF_OS_DESK', 'agent1.enquiries@ke.sip.africastalking.com	'),
    ],
    'missions_desk' => [
        'emails' => [
            'missions@parkroadfellowship.org',
            'adulu@parkroadfellowship.org',
        ],
    ],
    'reports' => [
        'environment' => [
            'node_path' => env('PDF_NODE_PATH', '/usr/bin/node'),
            'npm_path' => env('PDF_NPM_PATH', '/usr/bin/npm'),
            'chromium_args' => [
                'no-sandbox',
                'disable-setuid-sandbox',
                'disable-gpu',
                'disable-web-security',
                'disable-features=IsolateOrigins,site-per-process,Crashpad',
                'disable-dev-shm-usage',
                'disable-accelerated-2d-canvas',
                'no-first-run',
                'no-zygote',
                'single-process',
                'disable-extensions',
            ],
        ],
    ],
];
