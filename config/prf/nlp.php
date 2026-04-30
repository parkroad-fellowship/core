<?php

return [
    /*
    |--------------------------------------------------------------------------
    | NLP Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for NLP (Natural Language Processing)
    | services. This file is for storing the various settings and credentials
    | needed to interact with NLP providers.
    |
    */

    'base_url' => env('PRF_NLP_BASE_URL', 'http://localhost:8005'),
    'api_key' => env('PRF_NLP_API_KEY'),

    'default_bot' => env('PRF_NLP_DEFAULT_BOT', 'Fridah'),

];
