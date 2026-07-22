<?php

use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\VerifyRequestSignature;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: [
            __DIR__.'/../routes/api/v1.php',
            __DIR__.'/../routes/api/v2.php',
        ],
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');

        $middleware->api(append: [
            SecurityHeaders::class,
            VerifyRequestSignature::class,
        ]);

        $middleware->alias([
            'feature' => \App\Http\Middleware\CheckFeature::class,
            'tenant.initialized' => \App\Http\Middleware\EnsureTenantIsInitialized::class,
            'tenant.validate' => \App\Http\Middleware\ValidateTenant::class,
        ]);

        $middleware->prependToPriorityList(\Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests::class, \App\Http\Middleware\EnsureTenantIsInitialized::class);

        $middleware->throttleApi('api');

        $middleware->validateCsrfTokens(except: [
            'broadcasting/*',
            'telescope/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
