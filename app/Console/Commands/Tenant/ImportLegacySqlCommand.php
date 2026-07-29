<?php

namespace App\Console\Commands\Tenant;

use App\Actions\Tenant\AddTenantMemberAction;
use App\Actions\Tenant\CreateTenantAction;
use App\Models\PersonalAccessToken;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportLegacySqlCommand extends Command
{
    protected $signature = 'tenants:import-legacy-sql
        {--file= : Path to local legacy dump file (.dump/.backup/.sql)}
        {--backup-disk= : Filesystem disk containing backup archive (e.g. s3, azure)}
        {--backup-file= : Backup archive path on disk; if omitted, latest zip under --backup-path is used}
        {--backup-path= : Directory/prefix on backup disk used to discover latest backup zip}
        {--name= : Tenant name (default: "Parkroad Fellowship")}
        {--slug= : Tenant slug (auto-generated if omitted)}
        {--admin-email= : Admin user email to promote after import}
        {--force : Skip confirmation prompt}';

    protected $description = 'Import a pre-tenant PostgreSQL dump into a new single tenant';

    private string $tenantId;

    private string $tempDatabase;

    private array $dbConfig;

    /**
     * @var array<int, string>
     */
    private array $temporaryFiles = [];

    public function handle(): int
    {
        $file = $this->resolveImportSource();

        if (! $file) {
            return self::FAILURE;
        }

        $format = $this->detectDumpFormat($file);

        if ($format === 'unknown') {
            $this->error('Unrecognized dump format. Expected a PostgreSQL custom dump or plain SQL file.');

            return self::FAILURE;
        }

        try {
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

            if (! $this->restoreToTempDatabase($file, $format)) {
                $this->error('Restore failed.');
                $this->dropTempDatabase();

                return self::FAILURE;
            }

            $restoredTables = $this->countTempTables();

            if ($restoredTables === 0) {
                $this->error('Restore completed but no tables were found in the temporary database.');
                $this->error('This usually means the dump could not be interpreted by the available restore tools.');
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

            if (! $this->promoteAdminUser($tenant)) {
                $this->error('Admin promotion failed.');

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
        } finally {
            $this->cleanupTemporaryFiles();
        }
    }

    private function resolveImportSource(): ?string
    {
        $file = $this->option('file');

        if (is_string($file) && $file !== '') {
            if (! is_readable($file)) {
                $this->error('The --file option must point to a readable dump file.');

                return null;
            }

            return $file;
        }

        return $this->resolveImportSourceFromBackup();
    }

    private function resolveImportSourceFromBackup(): ?string
    {
        $disk = $this->resolveBackupDisk();
        $backupFile = $this->option('backup-file');
        $backupPath = (string) ($this->option('backup-path') ?: '');

        if (! $this->isConfiguredDisk($disk)) {
            $this->error("Backup disk [{$disk}] is not configured in filesystems.php.");

            return null;
        }

        if (! is_string($backupFile) || $backupFile === '') {
            $backupFile = $this->findLatestBackupFile($disk, $backupPath);
        }

        if (! is_string($backupFile) || $backupFile === '') {
            $this->error('No backup source provided. Use --file or provide --backup-file/--backup-path.');

            return null;
        }

        $this->info("Downloading backup [{$backupFile}] from disk [{$disk}]...");

        $localBackup = $this->downloadBackupToLocal($disk, $backupFile);

        if (! $localBackup) {
            return null;
        }

        $dumpFile = $this->extractLegacyDumpFromBackup($localBackup);

        if (! $dumpFile) {
            return null;
        }

        $this->info("Using extracted dump file: {$dumpFile}");

        return $dumpFile;
    }

    private function resolveBackupDisk(): string
    {
        $backupDiskOption = $this->option('backup-disk');

        if (is_string($backupDiskOption) && $backupDiskOption !== '') {
            return $backupDiskOption;
        }

        $destinationDisks = config('backup.backup.destination.disks', []);

        if (is_array($destinationDisks)) {
            foreach ($destinationDisks as $destinationDisk) {
                if (is_string($destinationDisk) && $destinationDisk !== '') {
                    return $destinationDisk;
                }
            }
        }

        return (string) config('filesystems.default', 'local');
    }

    private function isConfiguredDisk(string $disk): bool
    {
        return is_array(config("filesystems.disks.{$disk}"));
    }

    private function findLatestBackupFile(string $disk, string $prefix): ?string
    {
        try {
            $storage = Storage::disk($disk);
            $files = $storage->allFiles($prefix);
            $zipFiles = array_values(array_filter($files, static fn (string $file): bool => str_ends_with(strtolower($file), '.zip')));

            if ($zipFiles === []) {
                $this->error("No backup zip files found on disk [{$disk}] under [{$prefix}].");

                return null;
            }

            usort($zipFiles, function (string $a, string $b) use ($storage): int {
                return $storage->lastModified($b) <=> $storage->lastModified($a);
            });

            return $zipFiles[0] ?? null;
        } catch (\Throwable $e) {
            $this->error('Unable to discover latest backup file: '.$e->getMessage());

            return null;
        }
    }

    private function downloadBackupToLocal(string $disk, string $backupFile): ?string
    {
        try {
            $storage = Storage::disk($disk);

            if (! $storage->exists($backupFile)) {
                $this->error("Backup file [{$backupFile}] not found on disk [{$disk}].");

                return null;
            }

            $localPath = $this->makeTemporaryFilePath('legacy-backup', 'zip');

            $readStream = $storage->readStream($backupFile);
            $writeStream = fopen($localPath, 'w+b');

            if (! is_resource($readStream) || ! is_resource($writeStream)) {
                $this->error('Unable to open backup streams for download.');

                return null;
            }

            stream_copy_to_stream($readStream, $writeStream);
            fclose($readStream);
            fclose($writeStream);

            $this->registerTemporaryFile($localPath);

            return $localPath;
        } catch (\Throwable $e) {
            $this->error('Failed to download backup file: '.$e->getMessage());

            return null;
        }
    }

    private function extractLegacyDumpFromBackup(string $backupZip): ?string
    {
        $zip = new \ZipArchive;

        if ($zip->open($backupZip) !== true) {
            $this->error('Unable to open backup archive.');

            return null;
        }

        $candidates = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->getNameIndex($i);

            if (! is_string($entry)) {
                continue;
            }

            $entryLower = strtolower($entry);
            $isCandidate = str_ends_with($entryLower, '.dump')
                || str_ends_with($entryLower, '.backup')
                || str_ends_with($entryLower, '.sql')
                || str_ends_with($entryLower, '.sql.gz');

            if (! $isCandidate) {
                continue;
            }

            $score = 0;

            if (str_contains($entryLower, 'db-dumps/')) {
                $score += 100;
            }

            if (str_ends_with($entryLower, '.dump') || str_ends_with($entryLower, '.backup')) {
                $score += 40;
            }

            if (str_ends_with($entryLower, '.sql')) {
                $score += 20;
            }

            if (str_ends_with($entryLower, '.sql.gz')) {
                $score += 15;
            }

            $candidates[] = [
                'entry' => $entry,
                'score' => $score,
            ];
        }

        if ($candidates === []) {
            $zip->close();
            $this->error('No SQL/database dump file found inside backup archive.');

            return null;
        }

        usort($candidates, static fn (array $a, array $b): int => $b['score'] <=> $a['score']);

        $selectedEntry = (string) $candidates[0]['entry'];
        $entryLower = strtolower($selectedEntry);

        $extension = 'sql';

        if (str_ends_with($entryLower, '.dump')) {
            $extension = 'dump';
        } elseif (str_ends_with($entryLower, '.backup')) {
            $extension = 'backup';
        } elseif (str_ends_with($entryLower, '.sql.gz')) {
            $extension = 'sql.gz';
        }

        $extractedPath = $this->makeTemporaryFilePath('legacy-dump', $extension);

        $entryStream = $zip->getStream($selectedEntry);
        $targetStream = fopen($extractedPath, 'w+b');

        if (! is_resource($entryStream) || ! is_resource($targetStream)) {
            $zip->close();
            $this->error('Unable to extract selected dump file from archive.');

            return null;
        }

        stream_copy_to_stream($entryStream, $targetStream);
        fclose($entryStream);
        fclose($targetStream);
        $zip->close();

        $this->registerTemporaryFile($extractedPath);

        if (! str_ends_with($entryLower, '.sql.gz')) {
            return $extractedPath;
        }

        $unzippedPath = $this->makeTemporaryFilePath('legacy-dump', 'sql');

        $gzStream = gzopen($extractedPath, 'rb');
        $sqlStream = fopen($unzippedPath, 'w+b');

        if (! is_resource($gzStream) || ! is_resource($sqlStream)) {
            $this->error('Failed to decompress .sql.gz dump from backup.');

            return null;
        }

        while (! gzeof($gzStream)) {
            fwrite($sqlStream, (string) gzread($gzStream, 8192));
        }

        gzclose($gzStream);
        fclose($sqlStream);

        $this->registerTemporaryFile($unzippedPath);

        return $unzippedPath;
    }

    private function makeTemporaryFilePath(string $prefix, string $extension): string
    {
        $directory = storage_path('app/backup-temp');

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        return $directory.'/'.$prefix.'-'.Str::lower((string) Str::uuid()).'.'.$extension;
    }

    private function registerTemporaryFile(string $path): void
    {
        $this->temporaryFiles[] = $path;
    }

    private function cleanupTemporaryFiles(): void
    {
        foreach ($this->temporaryFiles as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    private function detectDumpFormat(string $file): string
    {
        $header = '';

        $handle = fopen($file, 'rb');

        if (is_resource($handle)) {
            $header = (string) fread($handle, 5);
            fclose($handle);
        }

        if ($header === 'PGDMP') {
            return 'pg_restore';
        }

        $fileLower = strtolower($file);

        if (str_ends_with($fileLower, '.dump') || str_ends_with($fileLower, '.backup')) {
            return 'pg_restore';
        }

        if (str_ends_with($fileLower, '.sql')) {
            return 'psql';
        }

        $result = Process::run(['file', $file]);
        $output = strtolower($result->output());

        if (str_contains($output, 'postgresql custom database dump')) {
            return 'pg_restore';
        }

        if (str_contains($output, 'sql') || str_contains($output, 'ascii text')) {
            return 'psql';
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

    private function restoreToTempDatabase(string $file, string $format): bool
    {
        if ($format === 'psql') {
            return $this->restoreSqlToTempDatabase($file);
        }

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

    private function restoreSqlToTempDatabase(string $file): bool
    {
        $result = Process::env(['PGPASSWORD' => $this->dbConfig['password']])->timeout(600)->run([
            'psql',
            '-v', 'ON_ERROR_STOP=1',
            '-h', $this->dbConfig['host'],
            '-p', $this->dbConfig['port'],
            '-U', $this->dbConfig['username'],
            '-d', $this->tempDatabase,
            '-f', $file,
        ]);

        if ($result->successful()) {
            return true;
        }

        $errorOutput = trim($result->errorOutput());
        $stdout = trim($result->output());

        $this->error('psql failed.');
        $this->line("  Exit code: {$result->exitCode()}");

        if ($errorOutput !== '') {
            $this->line("  Stderr: {$errorOutput}");
        }

        if ($stdout !== '') {
            $this->line("  Stdout: {$stdout}");
        }

        $this->line("  File: {$file}");
        $this->line("  Host: {$this->dbConfig['host']}:{$this->dbConfig['port']}");
        $this->line("  User: {$this->dbConfig['username']}");
        $this->line("  DB: {$this->tempDatabase}");

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
            "SELECT column_name FROM information_schema.columns WHERE table_schema = 'public' AND table_name = '{$table}' AND is_generated = 'NEVER' ORDER BY ordinal_position"
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
                    FROM dblink('host={$this->dbConfig['host']} port={$this->dbConfig['port']} dbname={$this->tempDatabase} user={$this->dbConfig['username']} password={$this->dbConfig['password']}', {$this->quoteDblinkQuery($dblinkQuery)})
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

    private function promoteAdminUser(Tenant $tenant): bool
    {
        $adminEmail = $this->option('admin-email');

        if (! $adminEmail) {
            return true;
        }

        $user = User::query()->where('email', $adminEmail)->first();

        if (! $user) {
            $this->error("Admin user with email [{$adminEmail}] not found.");

            return false;
        }

        tenancy()->initialize($tenant);

        try {
            (new \Database\Seeders\RolesAndPermissionsSeeder)->run();

            $user->assignRole('super admin');

            app(AddTenantMemberAction::class)->handle($tenant, $user, 'super admin');

            $this->info("Admin user [{$adminEmail}] promoted.");
        } catch (\Throwable $e) {
            $this->error('Admin promotion failed: '.$e->getMessage());

            return false;
        } finally {
            tenancy()->end();
        }

        return true;
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
