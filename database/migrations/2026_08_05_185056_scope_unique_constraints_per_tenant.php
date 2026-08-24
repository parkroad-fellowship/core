<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Indexes that must keep their current definition: primary keys, Spatie's
     * partial (tenant/global) role indexes, globally unique tokens, and media
     * uuids. Also every unique index that was already tenant-scoped before
     * this migration ran.
     */
    private array $excludedIndexes = [
        'media_uuid_unique',
        'personal_access_tokens_token_unique',
        'model_has_roles_global_role_model_type_unique',
        'model_has_roles_tenant_role_model_type_unique',
        'model_has_roles_pkey',
        'model_has_permissions_pkey',
        'app_settings_tenant_id_key_unique',
        'cohorts_tenant_id_slug_unique',
        'cohorts_tenant_id_start_date_unique',
        'courses_tenant_id_slug_unique',
        'lessons_tenant_id_slug_unique',
        'letters_tenant_id_slug_unique',
        'modules_tenant_id_slug_unique',
        'payments_tenant_id_access_code_unique',
        'payments_tenant_id_reference_unique',
        'payment_instructions_tenant_id_requisition_id_unique',
        'roles_tenant_id_name_guard_name_key',
        'students_tenant_id_name_unique',
        'tenant_user_tenant_id_user_id_unique',
    ];

    /**
     * Convert every global unique index on tenant-scoped tables into a
     * composite unique index scoped by tenant_id, so the same person or record
     * (same ULID/email/phone/slug) can exist once per tenant.
     */
    public function up(): void
    {
        foreach ($this->tenantScopedTables() as $table) {
            $this->scopeUniqueIndexes($table);
        }
    }

    /**
     * Restore the original global unique constraints for the indexes that this
     * migration converted.
     */
    public function down(): void
    {
        foreach ($this->tenantScopedTables() as $table) {
            $this->unscopeUniqueIndexes($table);
        }
    }

    /**
     * @return array<int, string>
     */
    private function tenantScopedTables(): array
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return [];
        }

        return array_values(
            array_unique(
                DB::table('information_schema.columns')
                    ->join('information_schema.tables', function ($join) {
                        $join->on(
                            'information_schema.tables.table_schema',
                            '=',
                            'information_schema.columns.table_schema',
                        )->on('information_schema.tables.table_name', '=', 'information_schema.columns.table_name');
                    })
                    ->where('information_schema.columns.table_schema', 'public')
                    ->where('information_schema.tables.table_type', 'BASE TABLE')
                    ->where('information_schema.columns.column_name', 'tenant_id')
                    ->pluck('information_schema.columns.table_name')
                    ->all(),
            ),
        );
    }

    private function scopeUniqueIndexes(string $table): void
    {
        foreach (Schema::getIndexes($table) as $index) {
            if ($index['primary'] || !$index['unique']) {
                continue;
            }

            if (in_array($index['name'], $this->excludedIndexes, true)) {
                continue;
            }

            $columns = $index['columns'];

            if (in_array('tenant_id', $columns, true)) {
                continue;
            }

            $this->dropUniqueConstraint($table, $index['name']);

            Schema::table($table, function (Blueprint $table) use ($columns) {
                $table->unique(array_merge(['tenant_id'], $columns));
            });
        }
    }

    private function unscopeUniqueIndexes(string $table): void
    {
        foreach (Schema::getIndexes($table) as $index) {
            if ($index['primary'] || !$index['unique']) {
                continue;
            }

            if (in_array($index['name'], $this->excludedIndexes, true)) {
                continue;
            }

            $columns = $index['columns'];

            if (!in_array('tenant_id', $columns, true)) {
                continue;
            }

            // Only reverse indexes whose name matches the conventional name
            // this migration would have generated.
            if ($index['name'] !== $this->constraintName($table, $columns)) {
                continue;
            }

            $originalColumns = array_values(array_diff($columns, ['tenant_id']));
            $originalName = $this->constraintName($table, $originalColumns);

            $this->dropUniqueConstraint($table, $index['name']);

            Schema::table($table, function (Blueprint $table) use ($originalColumns, $originalName) {
                $table->unique($originalColumns, $originalName);
            });
        }
    }

    private function dropUniqueConstraint(string $table, string $name): void
    {
        DB::statement("ALTER TABLE \"public\".\"{$table}\" DROP CONSTRAINT \"{$name}\"");
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function constraintName(string $table, array $columns): string
    {
        return $table . '_' . implode('_', $columns) . '_unique';
    }
};
