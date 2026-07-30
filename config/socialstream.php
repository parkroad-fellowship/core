<?php

return [
    'guard' => 'web',
    'middleware' => ['web'],
    'prompt' => 'Login with',
    'providers' => [
        'google',
    ],
    'features' => [
        'generate-missing-emails',
        'auth-existing-unlinked-users',
        'remember-session',
        'provider-avatars',
        'refresh-oauth-tokens',
    ],
    'home' => '/admin',
    'redirects' => [
        'login' => '/admin',
        'register' => '/admin',
        'login-failed' => '/login',
        'registration-failed' => '/register',
        'provider-linked' => '/user/profile',
        'provider-link-failed' => '/user/profile',
    ],
];
