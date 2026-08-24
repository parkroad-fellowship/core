<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private array $excludedTables = [
        'users',
        'tenants',
        'domains',
        'tenant_user',
        'connected_accounts',
        'jobs',
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
    ];

    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        foreach ($this->getAllTables() as $table) {
            if (in_array($table, $this->excludedTables, true)) {
                continue;
            }

            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }

            DB::statement("ALTER TABLE \"{$table}\" ALTER COLUMN \"tenant_id\" SET NOT NULL");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        foreach ($this->getAllTables() as $table) {
            if (in_array($table, $this->excludedTables, true)) {
                continue;
            }

            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }

            DB::statement("ALTER TABLE \"{$table}\" ALTER COLUMN \"tenant_id\" DROP NOT NULL");
        }
    }

    private function getAllTables(): array
    {
        if (DB::getDriverName() === 'sqlite') {
            $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");

            return array_map(fn($t) => $t->name, $tables);
        }

        $tables = DB::select(
            "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_type = 'BASE TABLE'",
        );

        return array_map(fn($t) => $t->table_name, $tables);
    }
};
