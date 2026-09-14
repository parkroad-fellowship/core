<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\PermissionRegistrar;
use Stancl\Tenancy\Events;
use Stancl\Tenancy\Listeners;
use Stancl\Tenancy\Middleware;

class TenancyServiceProvider extends ServiceProvider
{
    public static string $controllerNamespace = '';

    public function events()
    {
        return [
            Events\CreatingTenant::class => [],
            Events\TenantCreated::class => [],
            Events\SavingTenant::class => [],
            Events\TenantSaved::class => [],
            Events\UpdatingTenant::class => [],
            Events\TenantUpdated::class => [],
            Events\DeletingTenant::class => [],
            Events\TenantDeleted::class => [],

            Events\TenantMaintenanceModeEnabled::class => [],
            Events\TenantMaintenanceModeDisabled::class => [],

            Events\CreatingDomain::class => [],
            Events\DomainCreated::class => [],
            Events\SavingDomain::class => [],
            Events\DomainSaved::class => [],
            Events\UpdatingDomain::class => [],
            Events\DomainUpdated::class => [],
            Events\DeletingDomain::class => [],
            Events\DomainDeleted::class => [],

            Events\InitializingTenancy::class => [],
            Events\TenancyInitialized::class => [
                Listeners\BootstrapTenancy::class,
            ],

            Events\EndingTenancy::class => [],
            Events\TenancyEnded::class => [
                Listeners\RevertToCentralContext::class,
            ],

            Events\BootstrappingTenancy::class => [],
            Events\TenancyBootstrapped::class => [],
            Events\RevertingToCentralContext::class => [],
            Events\RevertedToCentralContext::class => [],

            Events\CreatingStorageSymlink::class => [],
            Events\StorageSymlinkCreated::class => [],
            Events\RemovingStorageSymlink::class => [],
            Events\StorageSymlinkRemoved::class => [],
        ];
    }

    protected function overrideUrlInTenantContext(): void {}

    public function register()
    {
        //
    }

    public function boot()
    {
        $this->bootEvents();
        $this->mapRoutes();

        $this->makeTenancyMiddlewareHighestPriority();
        $this->overrideUrlInTenantContext();

        Event::listen(Events\TenancyInitialized::class, function (Events\TenancyInitialized $event) {
            $permissionRegistrar = app(PermissionRegistrar::class);

            if ($tenant = $event->tenancy->tenant) {
                $permissionRegistrar->setPermissionsTeamId($tenant->getKey());
            }

            $permissionRegistrar->forgetCachedPermissions();

            if (app()->bound(\App\Contracts\Services\FirebaseManagerInterface::class)) {
                app(\App\Contracts\Services\FirebaseManagerInterface::class)->reset();
            }

            $this->applyTenantSessionVariable();
        });

        // PostgresRLSBootstrapper SETs my.current_tenant once, but any
        // purge/reconnect drops it for that PDO while FORCE RLS still
        // blocks writes. Re-apply on every new pgsql connection.
        Event::listen(ConnectionEstablished::class, function (ConnectionEstablished $event) {
            if ($event->connection->getDriverName() !== 'pgsql') {
                return;
            }

            if (!tenancy()->initialized || !tenancy()->tenant) {
                return;
            }

            $this->applyTenantSessionVariable($event->connection);
        });

        $this->restoreStaticTenantConnectionAfterRevert();
    }

    /**
     * Stancl's purgeTenantConnection() unsets database.connections.tenant
     * whenever tenancy ends (e.g. after every queued job in a worker).
     * That breaks anything resolving the tenant connection afterwards in
     * the same process (queued model restoration, Telescope watchers).
     * Re-apply the statically-defined connection from config/database.php.
     */
    protected function restoreStaticTenantConnectionAfterRevert(): void
    {
        $pristineTenantConnection = config('database.connections.tenant');

        if ($pristineTenantConnection === null) {
            return;
        }

        $restore = function () use ($pristineTenantConnection) {
            if (config('database.connections.tenant') === null) {
                config(['database.connections.tenant' => $pristineTenantConnection]);
            }
        };

        Event::listen(Events\RevertedToCentralContext::class, $restore);
        Event::listen(Events\TenancyEnded::class, $restore);
    }

    protected function bootEvents()
    {
        foreach ($this->events() as $event => $listeners) {
            foreach ($listeners as $listener) {
                Event::listen($event, $listener);
            }
        }
    }

    /**
     * Re-apply the RLS session variable on the given (or default) connection.
     *
     * Safe to call repeatedly; failures are logged and never break the request
     * since the PostgresRLSBootstrapper already performed the initial SET.
     */
    protected function applyTenantSessionVariable(?\Illuminate\Database\Connection $connection = null): void
    {
        try {
            $tenant = tenancy()->tenant;

            if (!$tenant) {
                return;
            }

            $variable = config('tenancy.rls.session_variable_name', 'my.current_tenant');
            $key = $tenant->getTenantKey();

            ($connection ?? DB::connection())->statement("SET {$variable} = '{$key}'");
        } catch (\Throwable $e) {
            Log::warning('Failed to apply tenant RLS session variable', ['error' => $e->getMessage()]);
        }
    }

    protected function mapRoutes()
    {
        $this->app->booted(function () {
            if (file_exists(base_path('routes/tenant.php'))) {
                Route::namespace(static::$controllerNamespace)
                    ->middleware('tenant')
                    ->group(base_path('routes/tenant.php'));
            }
        });
    }

    protected function makeTenancyMiddlewareHighestPriority()
    {
        $tenancyMiddleware = array_merge(
            [Middleware\PreventAccessFromUnwantedDomains::class],
            config('tenancy.identification.middleware'),
        );

        foreach (array_reverse($tenancyMiddleware) as $middleware) {
            $this->app[\Illuminate\Contracts\Http\Kernel::class]->prependToMiddlewarePriority($middleware);
        }
    }
}
