<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payment_types', function (Blueprint $table) {
            $table->foreignId('ledger_category_id')->nullable()->after('description')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payment_types', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ledger_category_id');
        });
    }
};
