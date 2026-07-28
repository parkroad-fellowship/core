<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP INDEX IF EXISTS users_tenant_id_index');
        DB::statement('DROP INDEX IF EXISTS users_tenant_id_email_unique');

        if (Schema::hasColumn('users', 'tenant_id')) {
            Schema::table('users', function ($table) {
                $table->dropColumn('tenant_id');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'tenant_id')) {
            Schema::table('users', function ($table) {
                $table->string('tenant_id', 36)->nullable()->after('id');
                $table->index('tenant_id');
            });
        }
    }
};
