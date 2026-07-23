<?php

namespace App\Console\Commands\Tenant;

use App\Actions\Tenant\CreateTenantAction;
use App\Models\PersonalAccessToken;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ImportLegacySqlCommand extends Command
{
    protected $signature = 'tenants:import-legacy-sql
        {--file= : Path to pg_restore dump file}
        {--name= : Tenant name (default: "Parkroad Fellowship")}
        {--slug= : Tenant slug (auto-generated if omitted)}
        {--admin-email= : Admin user email to promote after import}
        {--force : Skip confirmation prompt}';

    protected $description = 'Import a pre-tenant PostgreSQL dump into a new single tenant';

    private string $tenantId;

    private string $tempDatabase;

    private array $dbConfig;

    public function handle(): int
    {
        $file = $this->option('file');

        if (! $file || ! is_readable($file)) {
            $this->error('The --file option is required and must point to a readable dump file.');

            return self::FAILURE;
        }

        $format = $this->detectDumpFormat($file);

        if ($format === 'unknown') {
            $this->error('Unrecognized dump format. Expected a pg_restore custom-format dump.');

            return self::FAILURE;
        }

        $this->dbConfig = $this->getDbConfig();
        $this->tempDatabase = 'prf_import_'.Str::lower(Str::random(8));

        if (! $this->confirmRestore($file, $format)) {
            return self::SUCCESS;
        }

        $this->info('Running prerequisite migrations...');

        if (! $this->runPrerequisiteMigrations()) {
            $this->error('Prerequisite migration failed.');

            return self::FAILURE;
        }

        $this->info('Creating tenant...');

        $tenant = $this->createTenant();

        if (! $tenant) {
            $this->error('Tenant creation failed.');

            return self::FAILURE;
        }

        $this->tenantId = $tenant->id;

        $this->info("Tenant created: {$this->tenantId} ({$tenant->slug})");

        $this->info("Creating temporary database [{$this->tempDatabase}]...");

        DB::statement("CREATE DATABASE \"{$this->tempDatabase}\"");

        $this->info('Restoring dump into temporary database...');

        if (! $this->restoreToTempDatabase($file)) {
            $this->error('pg_restore failed.');
            $this->dropTempDatabase();

            return self::FAILURE;
        }

        $restoredTables = $this->countTempTables();

        if ($restoredTables === 0) {
            $this->error('Restore completed but no tables were found in the temporary database.');
            $this->error('This usually means pg_restore could not read the dump with the available client version.');
            $this->dropTempDatabase();

            return self::FAILURE;
        }

        $this->info("Temporary database contains {$restoredTables} restored tables.");

        $this->info('Merging data into main database with tenant_id...');

        try {
            $tablesBackfilled = $this->mergeTempToMain();
        } catch (\Throwable $e) {
            $this->error('Merge failed: '.$e->getMessage());
            $this->dropTempDatabase();

            return self::FAILURE;
        }

        if ($tablesBackfilled === 0) {
            $this->error('No tables were merged from the temporary database into the main database.');
            $this->error('Aborting to avoid a false-success import. Please verify dump compatibility and schema names.');
            $this->dropTempDatabase();

            return self::FAILURE;
        }

        $this->info("Merged {$tablesBackfilled} tables.");

        $this->info('Adding all imported users to tenant membership...');

        $usersAdded = $this->addUsersToTenant();

        if ($usersAdded < 0) {
            $this->error('Failed to add users to tenant_user pivot.');
            $this->dropTempDatabase();

            return self::FAILURE;
        }

        $this->info("Added {$usersAdded} users to tenant_user pivot.");

        $this->info('Dropping temporary database...');

        $this->dropTempDatabase();

        $this->info('Running remaining migrations...');

        if (! $this->runRemainingMigrations()) {
            $this->error('Remaining migration failed.');

            return self::FAILURE;
        }

        $this->info('Applying tenant RLS policies and grants...');

        if (! $this->runRlsSetup()) {
            $this->error('RLS setup failed.');

            return self::FAILURE;
        }

        $this->info('Revoking imported personal access tokens...');

        $tokensRevoked = $this->revokeTokens();

        $this->info("Revoked {$tokensRevoked} old tokens.");

        $this->info('Validating data integrity...');

        Artisan::call('tenants:validate-data');

        $this->line(Artisan::output());

        $this->printSummary($tenant, $tablesBackfilled, $usersAdded, $tokensRevoked);

        return self::SUCCESS;
    }

    private function detectDumpFormat(string $file): string
    {
        $result = Process::run(['file', $file]);

        if (str_contains($result->output(), 'PostgreSQL custom database dump')) {
            return 'pg_restore';
        }

        return 'unknown';
    }

    private function confirmRestore(string $file, string $format): bool
    {
        $this->line("File: {$file}");
        $this->line("Format: {$format}");
        $this->line("Temp database: {$this->tempDatabase}");
        $this->line('This will restore into a temp database, merge with tenant_id, then clean up.');

        if ($this->option('force')) {
            return true;
        }

        return $this->confirm('Do you want to proceed?', false);
    }

    private function getDbConfig(): array
    {
        $config = config('database.connections.'.config('database.default'));

        return [
            'host' => $config['host'] ?? '127.0.0.1',
            'port' => (string) ($config['port'] ?? '5432'),
            'username' => $config['username'] ?? 'root',
            'database' => $config['database'] ?? 'laravel',
            'password' => $config['password'] ?? '',
        ];
    }

    private function restoreToTempDatabase(string $file): bool
    {
        $result = Process::env(['PGPASSWORD' => $this->dbConfig['password']])->timeout(600)->run([
            'pg_restore',
            '--no-owner',
            '--no-privileges',
            '-h', $this->dbConfig['host'],
            '-p', $this->dbConfig['port'],
            '-U', $this->dbConfig['username'],
            '-d', $this->tempDatabase,
            $file,
        ]);

        $error = trim($result->errorOutput());

        if (str_contains($error, 'unsupported version')) {
            $this->warn('Local pg_restore is too old for this dump format.');

            return $this->restoreViaDocker($file);
        }

        if ($result->successful()) {
            return true;
        }

        if ($result->exitCode() === 1 && $error !== '') {
            $this->warn("pg_restore reported warnings/errors: {$error}");

            return true;
        }

        $this->error($error !== '' ? $error : 'pg_restore exited with a non-zero status.');

        return false;
    }

    private function restoreViaDocker(string $file): bool
    {
        $dockerCheck = Process::run(['docker', 'info']);

        if (! $dockerCheck->successful()) {
            $this->error('Docker is not available. Install PostgreSQL 17+ client tools: brew install postgresql@17');

            return false;
        }

        $this->info('Attempting restore via Docker (postgres:17 image)...');

        $realFile = realpath($file) ?: $file;
        $dockerHost = in_array($this->dbConfig['host'], ['127.0.0.1', 'localhost'], true)
            ? 'host.docker.internal'
            : $this->dbConfig['host'];

        $result = Process::env(['PGPASSWORD' => $this->dbConfig['password']])->timeout(600)->run([
            'docker', 'run', '--rm',
            '-e', "PGPASSWORD={$this->dbConfig['password']}",
            '-v', dirname($realFile).':/dump',
            'postgres:17',
            'pg_restore',
            '--no-owner',
            '--no-privileges',
            '-h', $dockerHost,
            '-p', $this->dbConfig['port'],
            '-U', $this->dbConfig['username'],
            '-d', $this->tempDatabase,
            '/dump/'.basename($realFile),
        ]);

        $errorOutput = trim($result->errorOutput());

        if ($result->exitCode() > 1 || $this->isFatalPgRestoreError($errorOutput)) {
            $this->error('Docker restore also failed:');
            $this->error($errorOutput !== '' ? $errorOutput : 'pg_restore exited with a non-zero status.');

            return false;
        }

        if ($result->exitCode() === 1 && $errorOutput !== '') {
            $this->warn("pg_restore warnings: {$errorOutput}");
        }

        return true;
    }

    private function isFatalPgRestoreError(string $errorOutput): bool
    {
        if ($errorOutput === '') {
            return false;
        }

        $fatalTokens = [
            'FATAL:',
            'could not connect',
            'connection to server',
            'unsupported version',
            'No such file or directory',
        ];

        foreach ($fatalTokens as $token) {
            if (str_contains($errorOutput, $token)) {
                return true;
            }
        }

        return false;
    }

    private function psql(string $database, string $sql): string
    {
        $result = Process::env(['PGPASSWORD' => $this->dbConfig['password']])->run([
            'psql',
            '-h', $this->dbConfig['host'],
            '-p', $this->dbConfig['port'],
            '-U', $this->dbConfig['username'],
            '-d', $database,
            '-t', '-A',
            '-c', $sql,
        ]);

        if (! $result->successful()) {
            $this->error("psql failed on [{$database}]: {$result->errorOutput()}");

            return '';
        }

        return trim($result->output());
    }

    private function getTempTables(): array
    {
        $output = $this->psql($this->tempDatabase,
            "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_type = 'BASE TABLE' ORDER BY table_name"
        );

        if ($output === '') {
            return [];
        }

        return array_filter(explode("\n", $output));
    }

    private function countTempTables(): int
    {
        $output = $this->psql(
            $this->tempDatabase,
            "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'public' AND table_type = 'BASE TABLE'"
        );

        return (int) $output;
    }

    private function getColumns(string $database, string $table): array
    {
        $output = $this->psql($database,
            "SELECT column_name FROM information_schema.columns WHERE table_schema = 'public' AND table_name = '{$table}' ORDER BY ordinal_position"
        );

        if ($output === '') {
            return [];
        }

        return array_filter(explode("\n", $output));
    }

    private function getRowCount(string $database, string $table): int
    {
        $output = $this->psql($database, "SELECT COUNT(*) FROM \"{$table}\"");

        return (int) $output;
    }

    private function mergeTempToMain(): int
    {
        $this->psql($this->dbConfig['database'], 'CREATE EXTENSION IF NOT EXISTS dblink');

        $tables = $this->getTempTables();
        $count = 0;
        $pending = [];

        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                $this->warn("  Skipping [{$table}] - not in main database");

                continue;
            }

            $hasTenantColumn = Schema::hasColumn($table, 'tenant_id');

            $tempColumns = $this->getColumns($this->tempDatabase, $table);
            $mainColumns = $this->getColumns($this->dbConfig['database'], $table);

            $commonColumns = array_values(array_intersect($tempColumns, $mainColumns));

            if (empty($commonColumns)) {
                $this->warn("  Skipping [{$table}] - no matching columns");

                continue;
            }

            $insertColumns = $commonColumns;

            if ($hasTenantColumn && ! in_array('tenant_id', $insertColumns, true)) {
                $insertColumns[] = 'tenant_id';
            }

            $columnList = implode(', ', array_map(fn ($c) => "\"{$c}\"", $insertColumns));

            $selectParts = [];
            foreach ($insertColumns as $col) {
                if ($col === 'tenant_id') {
                    $selectParts[] = "'{$this->tenantId}' AS \"tenant_id\"";
                } else {
                    $selectParts[] = "t.\"{$col}\"";
                }
            }
            $selectList = implode(', ', $selectParts);

            $rowCount = $this->getRowCount($this->tempDatabase, $table);

            if ($rowCount === 0) {
                $this->warn("  Skipping [{$table}] - 0 rows in dump");

                continue;
            }

            $tempColumnList = implode(', ', array_map(fn ($c) => "\"{$c}\"", $commonColumns));

            $dblinkQuery = "SELECT {$tempColumnList} FROM public.\"{$table}\"";

            $asParts = [];
            foreach ($commonColumns as $col) {
                $pgType = $this->getColumnType($this->tempDatabase, $table, $col);
                $asParts[] = "\"{$col}\" {$pgType}";
            }
            $asList = implode(', ', $asParts);

            $sql = "INSERT INTO \"{$table}\" ({$columnList})
                    SELECT {$selectList}
                    FROM dblink('dbname={$this->tempDatabase}', {$this->quoteDblinkQuery($dblinkQuery)})
                    AS t({$asList})
                    ON CONFLICT DO NOTHING";

            $pending[$table] = [
                'sql' => $sql,
                'rowCount' => $rowCount,
            ];
        }

        $failedTables = [];

        while (! empty($pending)) {
            $mergedInThisPass = 0;

            foreach ($pending as $table => $plan) {
                try {
                    $this->info("  Merging [{$table}] ({$plan['rowCount']} rows)...");
                    DB::statement($plan['sql']);
                    $this->syncTableSequence($table);
                    unset($pending[$table]);
                    unset($failedTables[$table]);
                    $mergedInThisPass++;
                    $count++;
                } catch (\Throwable $e) {
                    $failedTables[$table] = $e->getMessage();
                }
            }

            if ($mergedInThisPass === 0) {
                foreach ($failedTables as $table => $error) {
                    $this->error("  Failed to merge [{$table}]: {$error}");
                }

                break;
            }
        }

        if (! empty($pending)) {
            throw new \RuntimeException('Merge stopped due to unresolved foreign key dependencies.');
        }

        return $count;
    }

    private function syncTableSequence(string $table): void
    {
        if (! Schema::hasColumn($table, 'id')) {
            return;
        }

        $sequence = DB::selectOne(
            'SELECT pg_get_serial_sequence(?, ?) AS seq',
            ["public.{$table}", 'id']
        );

        $sequenceName = $sequence?->seq;

        if (! $sequenceName) {
            return;
        }

        DB::statement("SELECT setval('{$sequenceName}', COALESCE((SELECT MAX(id) FROM \"{$table}\"), 1), true)");
    }

    private function getColumnType(string $database, string $table, string $column): string
    {
        $output = $this->psql($database,
            "SELECT data_type FROM information_schema.columns WHERE table_schema = 'public' AND table_name = '{$table}' AND column_name = '{$column}'"
        );

        return match ($output) {
            'integer' => 'integer',
            'bigint' => 'bigint',
            'smallint' => 'smallint',
            'boolean' => 'boolean',
            'numeric', 'decimal' => 'numeric',
            'real' => 'real',
            'double precision' => 'double precision',
            'character varying', 'varchar' => 'text',
            'character', 'char' => 'text',
            'text' => 'text',
            'uuid' => 'uuid',
            'jsonb' => 'jsonb',
            'json' => 'json',
            'timestamp with time zone', 'timestamp without time zone' => 'timestamp',
            'date' => 'date',
            'time with time zone', 'time without time zone' => 'time',
            'bytea' => 'bytea',
            'ARRAY' => 'text',
            default => 'text',
        };
    }

    private function quoteDblinkQuery(string $query): string
    {
        return "'".str_replace("'", "''", $query)."'";
    }

    private function dropTempDatabase(): void
    {
        DB::statement("DROP DATABASE IF EXISTS \"{$this->tempDatabase}\"");
    }

    private function runPrerequisiteMigrations(): bool
    {
        $paths = [
            'database/migrations/2026_06_30_201722_add_tenant_id_to_domain_tables.php',
            'database/migrations/2026_06_30_201722_add_tenant_id_to_permission_tables.php',
            'database/migrations/2026_07_09_000100_create_tenant_user_table.php',
        ];

        foreach ($paths as $path) {
            $exitCode = Artisan::call('migrate', [
                '--path' => $path,
                '--force' => true,
            ]);

            if ($exitCode !== 0) {
                $this->error("Failed: {$path}");
                $this->line(Artisan::output());

                return false;
            }
        }

        return true;
    }

    private function createTenant(): ?Tenant
    {
        return app(CreateTenantAction::class)->handle(
            name: $this->option('name') ?: 'Parkroad Fellowship',
            slug: $this->option('slug'),
            shouldProvision: false,
        );
    }

    private function addUsersToTenant(): int
    {
        try {
            return DB::affectingStatement(
                'INSERT INTO tenant_user (tenant_id, user_id, role, created_at, updated_at)
                 SELECT ?, users.id, ?, NOW(), NOW()
                 FROM users
                 ON CONFLICT (tenant_id, user_id) DO NOTHING',
                [$this->tenantId, 'member']
            );
        } catch (\Throwable $e) {
            $this->error('Unable to populate tenant_user membership: '.$e->getMessage());

            return -1;
        }
    }

    private function runRemainingMigrations(): bool
    {
        $exitCode = Artisan::call('migrate', ['--force' => true]);

        if ($exitCode !== 0) {
            $this->error(Artisan::output());

            return false;
        }

        return true;
    }

    private function runRlsSetup(): bool
    {
        $exitCode = Artisan::call('tenants:rls', ['--no-interaction' => true]);

        $this->line(Artisan::output());

        return $exitCode === 0;
    }

    private function revokeTokens(): int
    {
        return PersonalAccessToken::query()->delete();
    }

    private function printSummary(Tenant $tenant, int $tablesBackfilled, int $usersAdded, int $tokensRevoked): void
    {
        $this->newLine();
        $this->info('=== Import Summary ===');
        $this->line("Tenant ID:   {$tenant->id}");
        $this->line("Tenant Name: {$tenant->name}");
        $this->line("Tenant Slug: {$tenant->slug}");
        $this->line("Tables Merged: {$tablesBackfilled}");
        $this->line("Users Added to Pivot: {$usersAdded}");
        $this->line("Old Tokens Revoked: {$tokensRevoked}");
        $this->newLine();
    }
}
