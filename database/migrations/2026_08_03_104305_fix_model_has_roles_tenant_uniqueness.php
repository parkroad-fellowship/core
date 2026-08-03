<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Remove pivot rows orphaned by tenant-context saves that wrote a NULL
        // tenant_id. A row assigning a tenant-scoped role to a user with a NULL
        // tenant_id is invisible to tenant-scoped reads and causes duplicate-key
        // errors when the role is re-added.
        DB::table('model_has_roles')
            ->whereNull('tenant_id')
            ->whereIn('role_id', function ($query) {
                $query->select('id')->from('roles')->whereNotNull('tenant_id');
            })
            ->delete();

        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->dropPrimary();
        });

        // Enforce uniqueness per tenant for tenant-context rows...
        DB::statement(
            'CREATE UNIQUE INDEX model_has_roles_tenant_role_model_type_unique '
            .'ON model_has_roles (tenant_id, role_id, model_id, model_type) '
            .'WHERE tenant_id IS NOT NULL'
        );

        // ...and per model for central (global role) rows with a NULL tenant_id.
        DB::statement(
            'CREATE UNIQUE INDEX model_has_roles_global_role_model_type_unique '
            .'ON model_has_roles (role_id, model_id, model_type) '
            .'WHERE tenant_id IS NULL'
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS model_has_roles_tenant_role_model_type_unique');
        DB::statement('DROP INDEX IF EXISTS model_has_roles_global_role_model_type_unique');

        // Rollback is only safe when no model holds the same role in more than
        // one tenant, otherwise the re-added primary key would conflict.
        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->primary(['role_id', 'model_id', 'model_type'], 'model_has_roles_pkey');
        });
    }
};
