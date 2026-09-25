<?php

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;

/**
 * Every create endpoint whose request has required fields must reject an empty payload with
 * a 422, never a 500. New resources are covered automatically.
 */
it('rejects an empty payload on every create endpoint', function () {
    $storeRoutes = collect(Route::getRoutes()->getRoutes())->filter(function (RoutingRoute $route) {
        if (
            !str_starts_with((string) $route->getName(), 'api.') || !str_ends_with((string) $route->getName(), '.store')
        ) {
            return false;
        }

        $controller = $route->getControllerClass();

        if ($controller === null || !method_exists($controller, 'store')) {
            return false;
        }

        $requestType = new ReflectionMethod($controller, 'store')->getParameters()[0]?->getType()?->getName();

        if ($requestType === null || !is_subclass_of($requestType, FormRequest::class)) {
            return false;
        }

        try {
            $rules = new $requestType()->rules();
        } catch (Throwable) {
            return true; // rules that need a live request still get exercised below
        }

        return collect($rules)->flatten()->contains('required');
    });

    expect($storeRoutes)->not->toBeEmpty();

    $request = actingAsTenantUser();

    $failures = $storeRoutes
        ->mapWithKeys(fn(RoutingRoute $route) => [
            $route->getName() => $request->postJson(route($route->getName()), [])->status(),
        ])
        ->reject(fn(int $status) => $status === 422)
        ->all();

    expect($failures)->toBe([]);
});
