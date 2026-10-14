<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->timestamp('status_checked_at')->nullable()->after('payment_status');
            $table->unsignedSmallInteger('status_check_count')->default(0)->after('status_checked_at');
            $table->timestamp('next_status_check_at')->nullable()->after('status_check_count');

            $table->index(['tenant_id', 'payment_status', 'next_status_check_at']);
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'payment_status', 'next_status_check_at']);
            $table->dropColumn(['status_checked_at', 'status_check_count', 'next_status_check_at']);
        });
    }
};
