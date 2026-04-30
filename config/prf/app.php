<?php

return [
    'gemini' => [
        'model' => 'models/gemini-3-pro-preview',
        'api_key' => env('GEMINI_API_KEY'),
        'max_output_tokens' => env('GEMINI_MAX_OUTPUT_TOKENS', 16384),
    ],
    'google_maps' => [
        'api_key' => env('GOOGLE_MAPS_API_KEY'),
    ],
    'azure_speech' => [
        'subscription_key' => env('AZURE_SPEECH_SUBSCRIPTION_KEY'),
        'region' => env('AZURE_SPEECH_REGION', 'southafricanorth'),
    ],
    'africas_talking' => [
        'username' => env('AFRICAS_TALKING_USERNAME'),
        'api_key' => env('AFRICAS_TALKING_API_KEY'),
        'webhook_secret' => env('AFRICAS_TALKING_WEBHOOK_SECRET'),
    ],
    'org_email_domain' => env('ORG_EMAIL_DOMAIN', 'parkroadfellowship.org'),
    'telescope_emails' => array_filter(array_map('trim', explode(',', env('TELESCOPE_EMAILS', '')))),
    'reports' => [
        'environment' => [
            'node_path' => env('PDF_NODE_PATH', '/usr/bin/node'),
            'npm_path' => env('PDF_NPM_PATH', '/usr/bin/npm'),
            'chrome_path' => env('PDF_CHROME_PATH', '/usr/bin/google-chrome-stable'),
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
