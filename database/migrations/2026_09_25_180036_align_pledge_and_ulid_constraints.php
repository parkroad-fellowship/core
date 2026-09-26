<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Brings the pledge tables and two older tables in line with every other tenant table:
 * a required tenant_id foreign key, unique ULIDs, and money as whole KES integers.
 */
return new class extends Migration {
    private const PLEDGE_TABLES = ['pledges', 'pledge_installments', 'pledge_reminders'];

    /**
     * Safe on databases that already have RLS policies (tenants:rls) and tenant foreign keys
     * (tenants:sync-rls): Postgres refuses to change the type of a column a policy uses, so only
     * the NOT NULL constraint is added, and constraints are created only when missing.
     */
    public function up(): void
    {
        foreach (self::PLEDGE_TABLES as $table) {
            if (DB::table($table)->whereNull('tenant_id')->exists()) {
                throw new RuntimeException("{$table} has rows without a tenant_id; assign them before migrating.");
            }

            DB::statement("ALTER TABLE {$table} ALTER COLUMN tenant_id SET NOT NULL");

            if (!$this->hasTenantForeignKey($table)) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint
                        ->foreign('tenant_id')
                        ->references('id')
                        ->on('tenants')
                        ->cascadeOnDelete()
                        ->cascadeOnUpdate();
                });
            }
        }

        foreach (['pledges', 'pledge_installments'] as $table) {
            if (Schema::getColumnType($table, 'amount') !== 'int8') {
                DB::statement("ALTER TABLE {$table} ALTER COLUMN amount TYPE bigint USING round(amount)::bigint");
            }
        }

        foreach (['mission_sessions', 'payment_types'] as $table) {
            if (!Schema::hasIndex($table, ['ulid'], 'unique')) {
                Schema::table($table, fn(Blueprint $blueprint) => $blueprint->unique('ulid'));
            }
        }
    }

    private function hasTenantForeignKey(string $table): bool
    {
        return collect(Schema::getForeignKeys($table))
            ->contains(
                fn(array $foreignKey): bool => (
                    $foreignKey['columns'] === ['tenant_id']
                    && $foreignKey['foreign_table'] === 'tenants'
                ),
            );
    }

    public function down(): void
    {
        Schema::table('payment_types', fn(Blueprint $table) => $table->dropUnique(['ulid']));
        Schema::table('mission_sessions', fn(Blueprint $table) => $table->dropUnique(['ulid']));

        foreach (['pledges', 'pledge_installments'] as $table) {
            Schema::table($table, fn(Blueprint $blueprint) => $blueprint->decimal('amount', 12, 2)->change());
        }

        foreach (self::PLEDGE_TABLES as $table) {
            Schema::table($table, fn(Blueprint $blueprint) => $blueprint->dropForeign(['tenant_id']));
            DB::statement("ALTER TABLE {$table} ALTER COLUMN tenant_id DROP NOT NULL");
        }
    }
};
