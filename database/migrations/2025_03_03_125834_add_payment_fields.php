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
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique(['order_tracking_id']);
            $table->dropColumn([
                'redirect_url',
                'order_meta',
                'order_tracking_id',
            ]);

            $table->string('reference')->nullable()->unique();
            $table->string('access_code')->nullable()->unique();
            $table->string('authorization_url')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique([
                'reference',
                'access_code',
            ]);
            $table->dropColumn([
                'reference',
                'access_code',
                'authorization_url',
            ]);

            $table->string('order_tracking_id')->nullable()->unique();
            $table->longText('redirect_url')->nullable();
            $table->json('order_meta')->nullable();
        });
    }
};
