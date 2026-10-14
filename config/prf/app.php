<?php

return [
    /*
     | Organisation defaults. Tenants override these through App Settings when tenancy starts
     | (App\Tenancy\Listeners\LoadTenantSettings).
     */
    'global_group' => 'All',
    'excluded_emails' => [],
    'head_office' => [
        'latitude' => '-1.2906674',
        'longitude' => '36.7690094',
    ],
    'missions_desk' => ['emails' => []],
    'chairpersons_desk' => ['emails' => []],
    'treasurers_desk' => ['emails' => []],
    'prayer_desk' => ['emails' => []],
    'follow_up_desk' => ['emails' => []],
    'music_desk' => ['emails' => []],
    'organising_secretary_desk' => ['emails' => []],
    'vice_chairpersons_desk' => ['emails' => []],
    'app_stores' => [
        'android' => ['url' => ''],
        'ios' => ['url' => ''],
        'huawei' => ['url' => '', 'app_id' => ''],
    ],
    'leadership_app' => [
        'android' => ['url' => ''],
        'ios' => ['url' => ''],
    ],
    'executive_committee' => ['roles' => []],
    'camp_committee' => ['emails' => []],
    'google_maps' => [
        'api_key' => env('GOOGLE_MAPS_API_KEY'),
    ],
    'azure_speech' => [
        'subscription_key' => env('AZURE_SPEECH_SUBSCRIPTION_KEY'),
        'region' => env('AZURE_SPEECH_REGION', 'southafricanorth'),
    ],
    'africas_talking' => [
        'callback_url' => '',
        'missions_desk' => '',
        'os_desk' => '',
        'webhook_secret' => env('AFRICAS_TALKING_WEBHOOK_SECRET'),
    ],
    /*
     | Addresses on these domains belong to their owners, never to a tenant, so member
     | emails are never generated on them (see App\Services\Members\MemberIdentityService).
     */
    'public_email_domains' => [
        'gmail.com',
        'googlemail.com',
        'yahoo.com',
        'yahoo.co.uk',
        'outlook.com',
        'hotmail.com',
        'live.com',
        'msn.com',
        'icloud.com',
        'me.com',
        'aol.com',
        'proton.me',
        'protonmail.com',
        'gmx.com',
        'mail.com',
        'yandex.com',
        'zoho.com',
    ],
    'telescope_emails' => array_filter(array_map('trim', explode(',', env('TELESCOPE_EMAILS', '')))),
    'giving' => [
        // Domains that map to the PRF tenant used by the public pledge page.
        'pledge_tenant_domains' => explode(',', env('PLEDGE_TENANT_DOMAINS', 'app.parkroadfellowship.org')),
        'reminder_lead_days' => (int) env('PLEDGE_REMINDER_LEAD_DAYS', 7),
        // Optional address to copy on every due reminder (e.g. the Treasurer).
        'reminder_cc_email' => env('PLEDGE_REMINDER_CC_EMAIL', ''),
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
];
