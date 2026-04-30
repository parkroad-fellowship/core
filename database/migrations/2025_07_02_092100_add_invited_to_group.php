<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('mission_subscriptions', function (Blueprint $table) {
            $table->boolean('invited_to_group')->default(false)->after('status')
                ->comment('Indicates if the member has been invited to the WhatsApp group for the mission');
            $table->timestamp('invited_to_group_at')->nullable()->after('invited_to_group')
                ->comment('Timestamp when the member was invited to the WhatsApp group');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mission_subscriptions', function (Blueprint $table) {
            $table->dropColumn([
                'invited_to_group',
                'invited_to_group_at',
            ]);
        });
    }
};
