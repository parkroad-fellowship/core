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
        Schema::table('prf_events', function (Blueprint $table) {
            $table->tinyInteger('responsible_desk')->nullable()->index();
            $table->tinyInteger('event_type')->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prf_events', function (Blueprint $table) {
            $table->dropColumn([
                'responsible_desk',
                'event_type',
            ]);
        });
    }
};
