<?php

namespace App\Console\Commands\Tenant;

use App\Actions\Tenant\AddTenantMemberAction;
use App\Actions\Tenant\CreateTenantAction;
use App\Models\PersonalAccessToken;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportLegacySQLCommand extends Command
{
    protected $signature = 'tenants:import-legacy-sql
        {--file= : Path to local legacy dump file (.dump/.backup/.sql)}
        {--backup-disk= : Filesystem disk containing backup archive (e.g. s3, azure)}
        {--backup-file= : Backup archive path on disk; if omitted, latest zip under --backup-path is used}
        {--backup-path= : Directory/prefix on backup disk used to discover latest backup zip}
        {--backup-container= : Override the container/bucket of --backup-disk (e.g. another Azure container sharing the same connection string)}
        {--name= : Tenant name (default: "Parkroad Fellowship")}
        {--slug= : Tenant slug (auto-generated if omitted)}
        {--admin-email= : Admin user email to promote after import}
        {--force : Skip confirmation prompt}';

    protected $description = 'Import a pre-tenant PostgreSQL dump into a new single tenant';

    private string $tenantId;

    private string $tempDatabase;

    /**
     * @var array{host: string, port: string, username: string, database: string, password: string}
     */
    private array $dbConfig;

    private ?Filesystem $backupStorage = null;

    /**
     * @var array<int, string>
     */
    private array $temporaryFiles = [];

    /**
     * Map of legacy dump user id => main users.id after the users merge.
     *
     * @var array<int, int>
     */
    private array $userMap = [];

    /**
     * Cache of temp database columns keyed by table name.
     *
     * @var array<string, array<int, string>>
     */
    private array $tempColumnsCache = [];

    /**
     * Cache of main database NOT NULL columns keyed by table name.
     *
     * @var array<string, array<string, true>>
     */
    private array $notNullColumnsCache = [];

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

            $this->info('Merging users from the dump (deduplicated by email)...');

            if (! $this->mergeUsersFromTemp()) {
                $this->error('Users merge failed.');
                $this->dropTempDatabase();

                return self::FAILURE;
            }

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

            $this->info('Linking members and related rows to the imported users...');

            if (! $this->remapUserReferences()) {
                $this->error('Failed to link members to imported users.');
                $this->dropTempDatabase();

                return self::FAILURE;
            }

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
                $this->warn('Admin promotion skipped. Promote a user later with: php artisan tenants:add-member {tenant} {email} --role="super admin"');
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

    private function getBackupStorage(string $disk): Filesystem
    {
        if ($this->backupStorage !== null) {
            return $this->backupStorage;
        }

        $container = $this->option('backup-container');

        if (! is_string($container) || $container === '') {
            $this->backupStorage = Storage::disk($disk);

            return $this->backupStorage;
        }

        $config = config("filesystems.disks.{$disk}");

        if (! is_array($config) || ! isset($config['connection_string'])) {
            $this->error('The --backup-container option requires a disk with a connection string.');

            throw new \RuntimeException('Cannot override container on disk without a connection string.');
        }

        $this->info("Overriding backup container to [{$container}] on disk [{$disk}].");

        $this->backupStorage = Storage::build(array_merge($config, [
            'container' => $container,
        ]));

        return $this->backupStorage;
    }

    private function findLatestBackupFile(string $disk, string $prefix): ?string
    {
        try {
            $storage = $this->getBackupStorage($disk);
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
            $storage = $this->getBackupStorage($disk);

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

    /**
     * @return array{host: string, port: string, username: string, database: string, password: string}
     */
    private function getDbConfig(): array
    {
        /** @var string $defaultConnection */
        $defaultConnection = config('database.default');

        /** @var array<string, mixed> $config */
        $config = config('database.connections.'.$defaultConnection);

        $defaults = [
            'host' => '127.0.0.1',
            'port' => '5432',
            'username' => 'root',
            'database' => 'laravel',
            'password' => '',
        ];

        $values = [];

        foreach ($defaults as $key => $default) {
            $values[$key] = isset($config[$key]) && is_string($config[$key]) ? $config[$key] : $default;
        }

        return $values;
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

    /**
     * @return array<int, string>
     */
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

    /**
     * @return array<int, string>
     */
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

    private function mergeUsersFromTemp(): bool
    {
        $tempColumns = $this->getColumns($this->tempDatabase, 'users');
        $mainColumns = $this->getColumns($this->dbConfig['database'], 'users');

        if ($tempColumns === [] || $mainColumns === []) {
            $this->info('No users table found in the dump to merge.');

            return true;
        }

        $columns = array_values(array_filter(
            array_intersect($tempColumns, $mainColumns),
            static fn (string $column): bool => $column !== 'id',
        ));

        if ($columns === []) {
            $this->error('No shared columns found between the dump users table and the main users table.');

            return false;
        }

        $columnList = implode(', ', array_map(fn (string $column): string => "\"{$column}\"", $columns));

        $output = $this->psql(
            $this->tempDatabase,
            "SELECT json_agg(row_to_json(t)) FROM (SELECT {$columnList} FROM public.users ORDER BY id) t"
        );

        if ($output === '' || $output === 'null') {
            $this->info('No users found in the dump to merge.');

            return true;
        }

        $rows = json_decode($output, true);

        if (! is_array($rows)) {
            $this->error('Unable to parse the dump users payload.');

            return false;
        }

        /** @var list<array<string, mixed>> $rows */
        $rows = array_values($rows);

        $existingByEmail = DB::table('users')
            ->select('id', 'email')
            ->get()
            ->mapWithKeys(function ($user): array {
                /** @var string $email */
                $email = $user->email;
                /** @var int $id */
                $id = $user->id;

                return [strtolower(trim($email)) => $id];
            })
            ->all();

        $this->userMap = [];

        foreach ($rows as $row) {
            $email = isset($row['email']) && is_string($row['email'])
                ? strtolower(trim($row['email']))
                : '';

            if ($email === '') {
                $this->warn('Skipping dump user without an email.');

                continue;
            }

            $dumpId = isset($row['id']) && is_numeric($row['id']) ? (int) $row['id'] : 0;

            if (array_key_exists($email, $existingByEmail)) {
                $this->userMap[$dumpId] = $existingByEmail[$email];

                continue;
            }

            $data = ['email' => $email];

            foreach ($columns as $column) {
                if ($column === 'email' || ! array_key_exists($column, $row)) {
                    continue;
                }

                $data[$column] = is_array($row[$column])
                    ? json_encode($row[$column])
                    : $row[$column];
            }

            if (blank($data['ulid'] ?? null)) {
                $data['ulid'] = (string) Str::ulid();
            }

            try {
                $newId = DB::table('users')->insertGetId($data);
            } catch (\Throwable $e) {
                $this->error('Unable to insert dump user: '.$e->getMessage());

                return false;
            }

            $this->userMap[$dumpId] = $newId;
            $existingByEmail[$email] = $newId;
        }

        $this->info('Merged '.count($this->userMap).' users from the dump.');

        return true;
    }

    private function mergeTempToMain(): int
    {
        $this->psql($this->dbConfig['database'], 'CREATE EXTENSION IF NOT EXISTS dblink');

        $tables = $this->getTempTables();
        $count = 0;
        $pending = [];

        $offsets = $this->resolveIdOffsets($tables);
        $foreignKeys = $this->getForeignKeys();

        $userMapValues = $this->userMap === []
            ? ''
            : collect($this->userMap)
                ->map(fn (int $mainId, int $dumpId): string => "({$dumpId}::bigint, {$mainId}::bigint)")
                ->implode(', ');

        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                $this->warn("  Skipping [{$table}] - not in main database");

                continue;
            }

            if ($table === 'users') {
                $this->info('  Skipping [users] - merged separately (deduplicated by email)');

                continue;
            }

            $hasTenantColumn = Schema::hasColumn($table, 'tenant_id');

            $tempColumns = $this->getTempColumns($table);
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

            $tableFks = $foreignKeys[$table] ?? [];
            $offset = $offsets[$table] ?? 0;

            $columnList = implode(', ', array_map(fn ($c) => "\"{$c}\"", $insertColumns));

            $selectParts = [];
            $parentJoins = [];
            $userMapColumn = null;
            $nullGuards = [];

            foreach ($insertColumns as $col) {
                if ($col === 'tenant_id') {
                    $selectParts[] = "'{$this->tenantId}' AS \"tenant_id\"";

                    continue;
                }

                $expression = "t.\"{$col}\"";

                if ($col === 'id' && $offset > 0) {
                    $expression = "(t.\"id\" + {$offset})";
                } elseif (isset($tableFks[$col])) {
                    $parent = $tableFks[$col];

                    if ($parent === 'users' && $userMapValues !== '') {
                        $userMapColumn ??= $col;
                        $expression = "COALESCE(u.main_id, t.\"{$col}\")";
                    } else {
                        $remap = $this->buildParentRemap($parent, $col, $this->getTempColumns($parent));

                        if ($remap !== null) {
                            [$parentJoin, $mapped] = $remap;
                            $parentJoins[] = $parentJoin;
                            $expression = $mapped;
                        } elseif (($offsets[$parent] ?? 0) > 0) {
                            $expression = "(t.\"{$col}\" + {$offsets[$parent]})";
                        }
                    }

                    if ($this->isColumnNotNull($table, $col)) {
                        $nullGuards[] = "{$expression} IS NOT NULL";
                    }
                }

                $selectParts[] = "{$expression} AS \"{$col}\"";
            }
            $selectList = implode(', ', $selectParts);

            $join = '';

            if ($userMapColumn !== null) {
                $join = " LEFT JOIN (VALUES {$userMapValues}) AS u(dump_id, main_id) ON u.dump_id = t.\"{$userMapColumn}\"";
            }

            if ($parentJoins !== []) {
                $join .= ' '.implode(' ', $parentJoins);
            }

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
                    FROM dblink('{$this->getDblinkConnectionString()}', {$this->quoteDblinkQuery($dblinkQuery)})
                    AS t({$asList}){$join}";

            if ($nullGuards !== []) {
                $sql .= ' WHERE '.implode(' AND ', $nullGuards);
            }

            $sql .= ' ON CONFLICT DO NOTHING';

            $pending[$table] = [
                'sql' => $sql,
                'rowCount' => $rowCount,
                'guarded' => $nullGuards !== [],
            ];
        }

        $failedTables = [];

        while (! empty($pending)) {
            $mergedInThisPass = 0;

            foreach ($pending as $table => $plan) {
                try {
                    $this->info("  Merging [{$table}] ({$plan['rowCount']} rows)...");

                    if (! $plan['guarded']) {
                        DB::statement($plan['sql']);
                        $this->syncTableSequence($table);
                        unset($pending[$table]);
                        unset($failedTables[$table]);
                        $mergedInThisPass++;
                        $count++;

                        continue;
                    }

                    // Guarded inserts can legally land 0 rows while their parent
                    // rows have not been merged yet (tables are attempted in
                    // alphabetical order). Retry until the parents exist so the
                    // WHERE ... IS NOT NULL guard does not silently drop rows.
                    $before = DB::table($table)->count();
                    DB::statement($plan['sql']);
                    $inserted = DB::table($table)->count() - $before;

                    if ($this->hasCompletedMerge($plan['guarded'], $inserted)) {
                        $this->syncTableSequence($table);
                        unset($pending[$table]);
                        unset($failedTables[$table]);
                        $mergedInThisPass++;
                        $count++;
                    } else {
                        $failedTables[$table] = 'Inserted 0 rows (parent rows not yet merged or unresolvable)';
                    }
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

    /**
     * Resolve a legacy FK value to the matching row in the main database using
     * the parent's natural identity instead of blind id-offset arithmetic.
     *
     * When a parent insert is skipped by ON CONFLICT DO NOTHING because the row
     * already exists (same ULID, email, or Spatie name/guard_name), the computed
     * "dump id + offset" never exists and child rows would violate the foreign
     * key. These joins look the parent up by its real identity so children always
     * reference an existing row, whether the parent was freshly imported or was
     * already present.
     *
     * @param  array<int, string>  $dumpColumns
     * @return array{0: string, 1: string}|null [join sql, mapped column expression]
     */
    private function buildParentRemap(string $parent, string $column, array $dumpColumns): ?array
    {
        $connection = $this->getDblinkConnectionString();
        $alias = "p_{$column}";
        $mainAlias = "mp_{$column}";

        if (in_array('ulid', $dumpColumns, true)) {
            $dumpSelect = 'SELECT id, ulid FROM public."'.$parent.'"';
            $asList = '"id" bigint, "ulid" text';

            if ($parent === 'members' && in_array('email', $dumpColumns, true)) {
                $dumpSelect = 'SELECT id, ulid, email FROM public."members"';
                $asList = '"id" bigint, "ulid" text, "email" text';
            }

            $tenantScope = $this->isTenantScopedTable($parent)
                ? " AND {$mainAlias}.\"tenant_id\" = '{$this->tenantId}'"
                : '';

            $join = "LEFT JOIN dblink('{$connection}', '{$dumpSelect}') AS {$alias}({$asList})"
                ." ON {$alias}.\"id\" = t.\"{$column}\""
                ." LEFT JOIN \"{$parent}\" AS {$mainAlias} ON {$mainAlias}.\"ulid\" = {$alias}.\"ulid\"{$tenantScope}";

            $mapped = "{$mainAlias}.\"id\"";

            if ($parent === 'members' && in_array('email', $dumpColumns, true)) {
                $emailAlias = "{$mainAlias}_email";
                $emailScope = $this->isTenantScopedTable($parent)
                    ? " AND {$emailAlias}.\"tenant_id\" = '{$this->tenantId}'"
                    : '';
                $join .= " LEFT JOIN \"members\" AS {$emailAlias} ON {$mainAlias}.\"ulid\" IS NULL AND {$emailAlias}.\"email\" = {$alias}.\"email\"{$emailScope}";
                $mapped = "COALESCE({$mainAlias}.\"id\", {$emailAlias}.\"id\")";
            }

            return [$join, $mapped];
        }

        if ($parent === 'permissions' && in_array('name', $dumpColumns, true) && in_array('guard_name', $dumpColumns, true)) {
            $join = "LEFT JOIN dblink('{$connection}', 'SELECT id, name, guard_name FROM public.\"permissions\"') AS {$alias}(\"id\" bigint, \"name\" text, \"guard_name\" text)"
                ." ON {$alias}.\"id\" = t.\"{$column}\""
                ." LEFT JOIN \"permissions\" AS {$mainAlias} ON {$mainAlias}.\"name\" = {$alias}.\"name\" AND {$mainAlias}.\"guard_name\" = {$alias}.\"guard_name\"";

            return [$join, "{$mainAlias}.\"id\""];
        }

        if ($parent === 'roles' && in_array('name', $dumpColumns, true) && in_array('guard_name', $dumpColumns, true)) {
            $join = "LEFT JOIN dblink('{$connection}', 'SELECT id, name, guard_name FROM public.\"roles\"') AS {$alias}(\"id\" bigint, \"name\" text, \"guard_name\" text)"
                ." ON {$alias}.\"id\" = t.\"{$column}\""
                ." LEFT JOIN \"roles\" AS {$mainAlias} ON {$mainAlias}.\"name\" = {$alias}.\"name\" AND {$mainAlias}.\"guard_name\" = {$alias}.\"guard_name\" AND {$mainAlias}.\"tenant_id\" = '{$this->tenantId}'";

            return [$join, "{$mainAlias}.\"id\""];
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function getTempColumns(string $table): array
    {
        if (! isset($this->tempColumnsCache[$table])) {
            $this->tempColumnsCache[$table] = $this->getColumns($this->tempDatabase, $table);
        }

        return $this->tempColumnsCache[$table];
    }

    private function isTenantScopedTable(string $table): bool
    {
        return Schema::hasTable($table) && Schema::hasColumn($table, 'tenant_id');
    }

    /**
     * Whether the given column is NOT NULL in the main database.
     */
    private function isColumnNotNull(string $table, string $column): bool
    {
        if (! isset($this->notNullColumnsCache[$table])) {
            $output = $this->psql(
                $this->dbConfig['database'],
                "SELECT column_name FROM information_schema.columns WHERE table_schema = 'public' AND table_name = '{$table}' AND is_nullable = 'NO'"
            );

            $this->notNullColumnsCache[$table] = $output === ''
                ? []
                : array_fill_keys(explode("\n", $output), true);
        }

        return isset($this->notNullColumnsCache[$table][$column]);
    }

    /**
     * A table's merge is finished once it inserted rows, or when it had no
     * NOT NULL guard to wait on (0 inserted rows then just means the rows
     * were skipped by ON CONFLICT DO NOTHING, e.g. global rows like
     * permissions that already exist). Guarded tables with 0 inserted rows
     * are still waiting on their parent rows and must be retried.
     */
    private function hasCompletedMerge(bool $guarded, int $inserted): bool
    {
        return $inserted > 0 || ! $guarded;
    }

    private function getDblinkConnectionString(): string
    {
        return "host={$this->dbConfig['host']} port={$this->dbConfig['port']} dbname={$this->tempDatabase} user={$this->dbConfig['username']} password={$this->dbConfig['password']}";
    }

    /**
     * Compute, for every table with a numeric id, the offset to add to the
     * dump ids so they never collide with existing rows in the main database.
     *
     * @param  array<int, string>  $tables
     * @return array<string, int>
     */
    private function resolveIdOffsets(array $tables): array
    {
        $offsets = [];

        foreach ($tables as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'id')) {
                continue;
            }

            $idType = $this->getColumnType($this->dbConfig['database'], $table, 'id');

            if (! in_array($idType, ['smallint', 'integer', 'bigint'], true)) {
                continue;
            }

            $offsets[$table] = (int) $this->psql(
                $this->dbConfig['database'],
                "SELECT COALESCE(MAX(\"id\"), 0) FROM \"{$table}\""
            );
        }

        return $offsets;
    }

    /**
     * Foreign key relationships from the main database schema, keyed by
     * child table and column name, pointing to the referenced parent table.
     *
     * @return array<string, array<string, string>>
     */
    private function getForeignKeys(): array
    {
        $output = $this->psql($this->dbConfig['database'],
            "SELECT tc.table_name || '|' || kcu.column_name || '|' || ccu.table_name
             FROM information_schema.table_constraints tc
             JOIN information_schema.key_column_usage kcu
               ON tc.constraint_name = kcu.constraint_name
              AND tc.constraint_schema = kcu.constraint_schema
             JOIN information_schema.constraint_column_usage ccu
               ON ccu.constraint_name = tc.constraint_name
              AND ccu.constraint_schema = tc.constraint_schema
             WHERE tc.constraint_type = 'FOREIGN KEY'
               AND tc.table_schema = 'public'
               AND ccu.table_schema = 'public'
               AND ccu.table_name <> 'tenants'"
        );

        $foreignKeys = [];

        foreach (explode("\n", $output) as $line) {
            if ($line === '') {
                continue;
            }

            [$child, $column, $parent] = array_pad(explode('|', $line), 3, '');

            $foreignKeys[$child][$column] = $parent;
        }

        return $foreignKeys;
    }

    private function remapUserReferences(): bool
    {
        if ($this->userMap === []) {
            return true;
        }

        $valueList = collect($this->userMap)
            ->map(fn (int $mainId, int $dumpId): string => "({$dumpId}, {$mainId})")
            ->implode(', ');

        /**
         * @var list<array{table: string, column: string}> $targets
         */
        $targets = [
            ['table' => 'members', 'column' => 'user_id'],
            ['table' => 'students', 'column' => 'user_id'],
            ['table' => 'connected_accounts', 'column' => 'user_id'],
        ];

        foreach ($targets as $target) {
            $table = $target['table'];
            $column = $target['column'];

            try {
                $affected = DB::affectingStatement(
                    "UPDATE \"{$table}\"
                     SET \"{$column}\" = v.main_id
                     FROM (VALUES {$valueList}) AS v(dump_id, main_id)
                     WHERE \"{$table}\".\"{$column}\" = v.dump_id
                       AND \"{$table}\".\"tenant_id\" = ?",
                    [$this->tenantId]
                );
            } catch (\Throwable $e) {
                $this->error("Unable to link {$table}.{$column}: ".$e->getMessage());

                return false;
            }

            $this->info("  Linked {$affected} {$table}.{$column} rows to imported users.");
        }

        return true;
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
            'database/migrations/2026_08_05_185056_scope_unique_constraints_per_tenant.php',
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
        $dumpEmails = $this->psql($this->tempDatabase, 'SELECT email FROM public.users WHERE email IS NOT NULL');

        if ($dumpEmails === '') {
            $this->warn('No users found in the dump, so no tenant membership was added.');

            return 0;
        }

        $emails = array_values(array_unique(array_filter(
            array_map(
                static fn (string $email): string => strtolower(trim($email)),
                explode("\n", $dumpEmails),
            ),
            static fn (string $email): bool => $email !== '',
        )));

        if ($emails === []) {
            $this->warn('No users found in the dump, so no tenant membership was added.');

            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($emails), '?'));

        try {
            return DB::affectingStatement(
                'INSERT INTO tenant_user (tenant_id, user_id, role, created_at, updated_at)
                 SELECT ?, u.id, ?, NOW(), NOW()
                 FROM users u
                 WHERE u.email IN ('.$placeholders.')
                 ON CONFLICT (tenant_id, user_id) DO NOTHING',
                [$this->tenantId, 'member', ...$emails]
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

        tenancy()->initialize($tenant);

        try {
            (new \Database\Seeders\RolesAndPermissionsSeeder)->run();

            $user = User::query()->where('email', $adminEmail)->first();

            if (! $user) {
                $this->error("Admin user with email [{$adminEmail}] not found in tenant [{$tenant->id}].");

                return false;
            }

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
