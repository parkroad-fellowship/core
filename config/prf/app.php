<?php

return [
    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'max_output_tokens' => env('GEMINI_MAX_OUTPUT_TOKENS', 8192),
    ],
    'google_maps' => [
        'api_key' => env('GOOGLE_MAPS_APK_KEY'),
    ],
];
