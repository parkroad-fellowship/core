<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The RLS manager (PermissionControlledPostgreSQLSchemaManager) requires every
        // table to expose a constraint named *_pkey so it can GRANT REFERENCES on that
        // column. A real primary key is impossible here because tenant_id is NULL for
        // central (global role) assignments. A UNIQUE constraint with that name works:
        // Postgres treats NULLs as distinct in UNIQUE constraints, so per-tenant
        // uniqueness is preserved.
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement(
                'ALTER TABLE model_has_roles '
                .'ADD CONSTRAINT model_has_roles_pkey '
                .'UNIQUE (tenant_id, role_id, model_id, model_type)'
            );
        } else {
            // SQLite (test runner) has no information_schema key lookup, so a plain
            // unique index with the same name is sufficient there.
            Schema::table('model_has_roles', function (Blueprint $table) {
                $table->unique(['tenant_id', 'role_id', 'model_id', 'model_type'], 'model_has_roles_pkey');
            });
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE model_has_roles DROP CONSTRAINT model_has_roles_pkey');
        } else {
            Schema::table('model_has_roles', function (Blueprint $table) {
                $table->dropUnique('model_has_roles_pkey');
            });
        }
    }
};
