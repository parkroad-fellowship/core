<?php

use JoelButcher\Socialstream\Features;
use JoelButcher\Socialstream\Providers;

return [
    'guard' => 'web', // used if Fortify is not installed
    'middleware' => ['web'],
    'prompt' => 'Login with',
    'providers' => [
        Providers::google(),
    ],
    'features' => [
        Features::generateMissingEmails(),
        // Features::createAccountOnFirstLogin(),
        // Features::globalLogin(),
        Features::authExistingUnlinkedUsers(),
        Features::rememberSession(),
        Features::providerAvatars(),
        Features::refreshOAuthTokens(),
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
