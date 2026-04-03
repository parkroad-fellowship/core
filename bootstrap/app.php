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

        // Global security headers
        $middleware->append(SecurityHeaders::class);
    })
    // API middleware groups
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(append: [
            VerifyRequestSignature::class,
        ]);

        $middleware->throttleApi('api');
    })
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->validateCsrfTokens(except: [
            'broadcasting/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
