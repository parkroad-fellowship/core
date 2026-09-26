<?php

namespace App\Console\Commands\Tenant;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SyncRLSCommand extends Command
{
    protected $signature = 'tenants:sync-rls {--force : Run without confirmation}';

    protected $description = 'Ensure tenant_id FKs exist, then rebuild RLS policies (single deploy command)';

    /**
     * Tables that must never get a tenant_id FK.
     *
     * @var array<int, string>
     */
    private array $excludedTables = [
        'users',
        'tenants',
        'domains',
        'tenant_user',
        'jobs',
        'job_batches',
        'cache',
        'cache_locks',
        'sessions',
        'failed_jobs',
        'password_reset_tokens',
        'personal_access_tokens',
        'telescope_entries',
        'telescope_entries_tags',
        'telescope_monitoring',
        'pulse_entries',
        'pulse_values',
        'pulse_aggregates',
        'migrations',
    ];

    public function handle(): int
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->info('Skipping RLS sync (non-pgsql driver).');

            return self::SUCCESS;
        }

        $added = $this->ensureTenantForeignKeys();

        $this->info("Ensured tenant_id FKs ({$added} added). Rebuilding RLS policies...");

        $exitCode = Artisan::call('tenants:rls', ['--force' => true]);

        $this->line(Artisan::output());

        if ($exitCode !== 0) {
            $this->error('RLS policy rebuild failed.');

            return self::FAILURE;
        }

        $this->info('RLS sync complete.');

        return self::SUCCESS;
    }

    private function ensureTenantForeignKeys(): int
    {
        $added = 0;

        foreach ($this->getAllTables() as $table) {
            if (in_array($table, $this->excludedTables, true)) {
                continue;
            }

            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }

            if ($this->hasTenantForeignKey($table)) {
                continue;
            }

            try {
                Schema::table($table, function ($blueprint) {
                    $blueprint->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
                });

                $added++;
                $this->line("  Added tenant_id FK on [{$table}]");
            } catch (\Throwable $e) {
                $this->warn("  Skipped FK on [{$table}]: {$e->getMessage()}");
            }
        }

        return $added;
    }

    private function hasTenantForeignKey(string $table): bool
    {
        $result = DB::selectOne("SELECT COUNT(*) AS count
             FROM information_schema.table_constraints tc
             JOIN information_schema.key_column_usage kcu
               ON tc.constraint_name = kcu.constraint_name
              AND tc.constraint_schema = kcu.constraint_schema
             JOIN information_schema.constraint_column_usage ccu
               ON ccu.constraint_name = tc.constraint_name
              AND ccu.constraint_schema = tc.constraint_schema
             WHERE tc.constraint_type = 'FOREIGN KEY'
               AND tc.table_schema = 'public'
               AND tc.table_name = ?
               AND kcu.column_name = 'tenant_id'
               AND ccu.table_name = 'tenants'
               AND ccu.column_name = 'id'", [$table]);

        return (int) ($result->count ?? 0) > 0;
    }

    /**
     * @return array<int, string>
     */
    private function getAllTables(): array
    {
        $tables = DB::select(
            "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_type = 'BASE TABLE'",
        );

        return array_map(fn($t) => $t->table_name, $tables);
    }
}
