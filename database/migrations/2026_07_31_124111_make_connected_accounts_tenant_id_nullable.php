<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('connected_accounts', function ($table) {
            $table->string('tenant_id', 36)->nullable()->change();
        });
    }

    public function down(): void
    {
        // Backward migration is not supported for this change, as it may lead to data loss if any tenant_id values are null.
    }
};
