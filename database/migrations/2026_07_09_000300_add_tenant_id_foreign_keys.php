<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $excludedTables = [
        'users',
        'tenants',
        'domains',
        'tenant_user',
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
        $allTables = $this->getAllTables();

        foreach ($allTables as $table) {
            if (in_array($table, $this->excludedTables, true)) {
                continue;
            }

            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }

            try {
                Schema::table($table, function ($table) {
                    $table->foreign('tenant_id')
                        ->references('id')
                        ->on('tenants')
                        ->onDelete('cascade');
                });
            } catch (\Throwable $e) {
                // Foreign key may already exist
            }
        }
    }

    public function down(): void
    {
        $allTables = $this->getAllTables();

        foreach ($allTables as $table) {
            if (in_array($table, $this->excludedTables, true)) {
                continue;
            }

            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }

            try {
                Schema::table($table, function ($table) {
                    $table->dropForeign(['tenant_id']);
                });
            } catch (\Throwable $e) {
                // Foreign key may not exist
            }
        }
    }

    private function getAllTables(): array
    {
        $tables = DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_type = 'BASE TABLE'");

        return array_map(fn ($t) => $t->table_name, $tables);
    }
};
