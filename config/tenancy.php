<?php

declare(strict_types=1);

use Stancl\Tenancy\Bootstrappers;
use Stancl\Tenancy\Enums\RouteMode;
use Stancl\Tenancy\Middleware;
use Stancl\Tenancy\Resolvers;

return [
    'models' => [
        'tenant' => App\Models\Tenant::class,
        'domain' => Stancl\Tenancy\Database\Models\Domain::class,
        'impersonation_token' => Stancl\Tenancy\Database\Models\ImpersonationToken::class,

        'tenant_key_column' => 'tenant_id',

        'id_generator' => App\Helpers\TenancyULIDGenerator::class,
    ],

    'identification' => [
        'central_domains' => explode(',', env('TENANCY_CENTRAL_DOMAINS', 'prf.test,localhost')),

        'tenant_parameter_name' => 'tenant',

        'default_middleware' => Middleware\InitializeTenancyByRequestData::class,

        'middleware' => [
            Middleware\InitializeTenancyByDomain::class,
            Middleware\InitializeTenancyBySubdomain::class,
            Middleware\InitializeTenancyByDomainOrSubdomain::class,
            Middleware\InitializeTenancyByPath::class,
            Middleware\InitializeTenancyByRequestData::class,
            Middleware\InitializeTenancyByOriginHeader::class,
        ],

        'domain_identification_middleware' => [
            Middleware\InitializeTenancyByDomain::class,
            Middleware\InitializeTenancyBySubdomain::class,
            Middleware\InitializeTenancyByDomainOrSubdomain::class,
        ],

        'path_identification_middleware' => [
            Middleware\InitializeTenancyByPath::class,
        ],

        'resolvers' => [
            Resolvers\DomainTenantResolver::class => [
                'cache' => false,
                'cache_ttl' => 3600,
                'cache_store' => null,
            ],
            Resolvers\PathTenantResolver::class => [
                'tenant_parameter_name' => 'tenant',
                'tenant_model_column' => null,
                'tenant_route_name_prefix' => 'tenant.',
                'allowed_extra_model_columns' => [],
                'cache' => false,
                'cache_ttl' => 3600,
                'cache_store' => null,
            ],
            Resolvers\RequestDataTenantResolver::class => [
                'header' => 'X-Tenant',
                'cookie' => null,
                'query_parameter' => null,

                'tenant_model_column' => null,

                'cache' => false,
                'cache_ttl' => 3600,
                'cache_store' => null,
            ],
        ],
    ],

    'bootstrappers' => [
        Bootstrappers\CacheTenancyBootstrapper::class,
        Bootstrappers\FilesystemTenancyBootstrapper::class,
        Bootstrappers\QueueTenancyBootstrapper::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Postgres RLS Bootstrapper (added conditionally in AppServiceProvider)
    |--------------------------------------------------------------------------
    |
    | The PostgresRLSBootstrapper is only added when using PostgreSQL since it
    | sets session variables that are not supported by other database drivers.
    |
     */

    'database' => [
        'central_connection' => env('DB_CONNECTION', 'pgsql'),

        'template_tenant_connection' => null,

        'tenant_host_connection_name' => 'tenant_host_connection',

        'prefix' => '',
        'suffix' => '',

        'managers' => [
            'sqlite' => Stancl\Tenancy\Database\TenantDatabaseManagers\SQLiteDatabaseManager::class,
            'mysql' => Stancl\Tenancy\Database\TenantDatabaseManagers\MySQLDatabaseManager::class,
            'mariadb' => Stancl\Tenancy\Database\TenantDatabaseManagers\MySQLDatabaseManager::class,
            'pgsql' => Stancl\Tenancy\Database\TenantDatabaseManagers\PostgreSQLDatabaseManager::class,
            'sqlsrv' => Stancl\Tenancy\Database\TenantDatabaseManagers\MicrosoftSQLDatabaseManager::class,
        ],

        'drop_tenant_databases_on_migrate_fresh' => false,
    ],

    'rls' => [
        'manager' => Stancl\Tenancy\RLS\PolicyManagers\TableRLSManager::class,

        'user' => [
            'username' => env('TENANCY_RLS_USERNAME'),
            'password' => env('TENANCY_RLS_PASSWORD'),
        ],

        'session_variable_name' => 'my.current_tenant',
    ],

    'cache' => [
        'prefix' => 'tenant_%tenant%_',
        'stores' => [
            // env('CACHE_STORE'),
        ],

        // 'scope_sessions' => in_array(env('SESSION_DRIVER'), ['redis', 'memcached', 'dynamodb', 'apc'], true),
        'scope_sessions' => false,

        'tag_base' => 'tenant',
    ],

    'filesystem' => [
        'suffix_base' => 'tenant',
        'disks' => [
            'local',
            'public',
        ],

        'root_override' => [
            'local' => '%storage_path%/app/',
            'public' => '%storage_path%/app/public/',
        ],

        'url_override' => [
            'public' => 'public-%tenant%',
        ],

        'scope_cache' => env('TENANCY_SCOPE_CACHE', true),

        'scope_sessions' => env('TENANCY_SCOPE_SESSIONS', true),

        'suffix_storage_path' => env('TENANCY_SUFFIX_STORAGE_PATH', true),

        'asset_helper_override' => false,
    ],

    'redis' => [
        'prefix' => 'tenant_%tenant%_',
        'prefixed_connections' => [
            'default',
        ],
    ],

    'features' => [],

    'routes' => true,

    'default_route_mode' => RouteMode::CENTRAL,

    'pending' => [
        'include_in_queries' => true,

        'count' => env('TENANCY_PENDING_COUNT', 5),
    ],

    'migration_parameters' => [
        '--force' => true,
        '--path' => [database_path('migrations/tenant')],
        '--schema-path' => database_path('schema/tenant-schema.dump'),
        '--realpath' => true,
    ],

    'seeder_parameters' => [
        '--class' => 'Database\Seeders\DatabaseSeeder',
    ],
];
