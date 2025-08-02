<?php

return [
    'make' => [
        'social_engine' => [
            'api_key' => env('MAKE_SOCIAL_ENGINE_API_KEY'),
            'webhook_url' => env('MAKE_SOCIAL_ENGINE_WEBHOOK_URL'),
            'profile_ids' => [
                'facebook' => env('MAKE_SOCIAL_ENGINE_PROFILE_FACEBOOK'),
                'instagram' => env('MAKE_SOCIAL_ENGINE_PROFILE_INSTAGRAM'),
                'threads' => env('MAKE_SOCIAL_ENGINE_PROFILE_THREADS'),
                'tiktok' => env('MAKE_SOCIAL_ENGINE_PROFILE_TIKTOK'),
            ],
        ],
    ],
];
