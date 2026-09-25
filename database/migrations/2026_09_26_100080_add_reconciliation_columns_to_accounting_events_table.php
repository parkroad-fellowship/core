<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('accounting_events', function (Blueprint $table) {
            $table->unsignedTinyInteger('reconciliation_status')->default(1)->after('status');
            $table->text('reconciliation_remarks')->nullable()->after('reconciliation_status');
            $table->timestamp('reconciled_at')->nullable()->after('reconciliation_remarks');
            $table
                ->foreignId('reconciled_by')
                ->nullable()
                ->after('reconciled_at')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('accounting_events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reconciled_by');
            $table->dropColumn(['reconciliation_status', 'reconciliation_remarks', 'reconciled_at']);
        });
    }
};
