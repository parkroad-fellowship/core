<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sms_logs', function (Blueprint $table) {
            $table->string('provider')->nullable()->after('id');
            $table->tinyInteger('status')->nullable()->after('message_id');
            $table->string('cost')->nullable()->after('status');
            $table->timestamp('delivered_at')->nullable()->after('cost');
        });
    }

    public function down(): void
    {
        Schema::table('sms_logs', function (Blueprint $table) {
            $table->dropColumn(['provider', 'status', 'cost', 'delivered_at']);
        });
    }
};
