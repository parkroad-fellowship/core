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
        Schema::table('weather_forecasts', function (Blueprint $table) {
            $table->longText('dressing_recommendations')->nullable();
            $table->longText('activity_recommendations')->nullable();
            $table->json('weather_recommendations')->default('[]');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('weather_forecasts', function (Blueprint $table) {
            $table->dropColumn([
                'dressing_recommendations',
                'activity_recommendations',
                'weather_recommendations',
            ]);
        });
    }
};
