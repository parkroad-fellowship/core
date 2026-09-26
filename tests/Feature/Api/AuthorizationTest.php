<?php

use App\Http\Controllers\Controller;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;

/**
 * Every resource listed through the base controller must refuse users without permission.
 * New resources are covered automatically as soon as their index route exists.
 */
it('refuses to list any resource for a user without permissions', function () {
    $indexRoutes = collect(Route::getRoutes()->getRoutes())->filter(function (RoutingRoute $route) {
        $controller = $route->getControllerClass();

        if (
            !str_starts_with((string) $route->getName(), 'api.') || !str_ends_with((string) $route->getName(), '.index')
        ) {
            return false;
        }

        if (
            $controller === null
            || !is_subclass_of($controller, Controller::class)
            || $route->getActionMethod() !== 'index'
        ) {
            return false;
        }

        // Only the shared index, which authorizes through the model's policy.
        return new ReflectionMethod($controller, 'index')->getDeclaringClass()->getName() === Controller::class;
    });

    expect($indexRoutes)->not->toBeEmpty();

    $request = actingAsTenantUser([]);

    $allowed = $indexRoutes
        ->reject(fn(RoutingRoute $route) => $request->getJson(route($route->getName()))->status() === 403)
        ->map(fn(RoutingRoute $route) => $route->getName())
        ->values()
        ->all();

    expect($allowed)->toBe([]);
});
