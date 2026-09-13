<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('payments', 'pledge_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->foreignId('pledge_id')->nullable()->after('member_id')->constrained();
                $table->index('pledge_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('payments', 'pledge_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropForeign(['pledge_id']);
                $table->dropIndex(['pledge_id']);
                $table->dropColumn('pledge_id');
            });
        }
    }
};
