<?php

return [
    'global_group' => 'All',
    'excluded_emails' => [
        'admin@parkroadfellowship.org',
        'approvals@parkroadfellowship.org',
        'chairperson@parkroadfellowship.org',
        'vicechair@parkroadfellowship.org',
        'treasurer@parkroadfellowship.org',
        'missions@parkroadfellowship.org',
        'organizingsec@parkroadfellowship.org',
        'follow-up@parkroadfellowship.org',
        'prayerdesk@parkroadfellowship.org',
        'adulu@parkroadfellowship.org',
        'musicdesk@parkroadfellowship.org',
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
    'chairpersons_desk' => [
        'emails' => [
            'chairperson@parkroadfellowship.org',
        ],
    ],
    'treasurers_desk' => [
        'emails' => [
            'treasurer@parkroadfellowship.org',
        ],
    ],
    'prayer_desk' => [
        'emails' => [
            'prayerdesk@parkroadfellowship.org',
            'onesmus.muthengi@parkroadfellowship.org',
        ],
    ],
    'follow_up_desk' => [
        'emails' => [
            'follow-up@parkroadfellowship.org',
        ],
    ],
    'music_desk' => [
        'emails' => [
            'musicdesk@parkroadfellowship.org',
        ],
    ],
    'organising_secretary_desk' => [
        'emails' => [
            'organizingsec@parkroadfellowship.org',
        ],
    ],
    'vice_chairpersons_desk' => [
        'emails' => [
            'vicechair@parkroadfellowship.org',
        ],
    ],
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
    'app_stores' => [
        'android' => [
            'url' => 'https://play.google.com/store/apps/details?id=org.parkroadfellowship.app&hl=en',
            'package_name' => 'org.parkroadfellowship.app',
        ],
        'ios' => [
            'url' => 'https://apps.apple.com/us/app/prf-missions/id6746665088',
            'bundle_id' => 'app.parkroadfellowship.org',
        ],
        'huawei' => [
            'url' => 'https://appgallery.huawei.com/app/C114264171',
            'package_name' => 'org.parkroadfellowship.app',
            'app_id' => 'C114264171',
        ],
    ],
    'executive_committee' => [
        'roles' => [
            'chairperson',
            'vice chairperson',
            'organising secretary',
            'missions secretary',
            'follow-up secretary',
            'treasurer',
            'prayer secretary',
            'music secretary',

        ],
    ],
    'camp_committee' => [
        '2025-2026' => [
            'emails' => [
                'follow-up@parkroadfellowship.org',
                'ogutu.arthur.odhiambo@parkroadfellowship.org',
                'beatrice.ndungu.wambui@parkroadfellowship.org',
                'brenda.kamau.nyambura@parkroadfellowship.org',
                'peter.gonzo.namaba@parkroadfellowship.org',
                // 'Anne Mlamba'
                // 'Joel Hosea'
            ],
        ],
    ],
];
