<?php

return [
    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'max_output_tokens' => env('GEMINI_MAX_OUTPUT_TOKENS', 8192),
    ],
    'google_maps' => [
        'api_key' => env('GOOGLE_MAPS_API_KEY'),
    ],
    'head_office' => [
        'latitude' => env('HEAD_OFFICE_LATITUDE', '-1.2906671'),
        'longitude' => env('HEAD_OFFICE_LONGITUDE', '36.7484092'),
    ],
];
