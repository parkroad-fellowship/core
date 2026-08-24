<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $tableNames = [
            'activity_log',
            config('permission.table_names')['roles'],
        ];

        foreach ($tableNames as $table) {
            Schema::table($table, function ($table) {
                $table->ulid('tenant_id')->nullable()->change();
            });
        }

        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->dropPrimary();
            $table->char('tenant_id', 26)->nullable()->change();
            $table->primary(['role_id', 'model_id', 'model_type']);
        });
    }

    public function down(): void
    {
        // Backward migration is not supported for this change, as it may lead to data loss if any tenant_id values are null.
    }
};
