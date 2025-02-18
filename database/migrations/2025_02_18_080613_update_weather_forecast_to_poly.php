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
            $table->bigInteger('weather_forecastable_id')->nullable();
            $table->tinyInteger('weather_forecastable_type')->nullable()->after('weather_forecastable_id');

            // Modify the mission_id column to be nullable so that we can have backwards
            // compatibility with the morph option
            $table->dropForeign(['mission_id']);
            $table->unsignedBigInteger('mission_id')->nullable()->change();
            $table->foreign('mission_id')->references('id')->on('missions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('weather_forecasts', function (Blueprint $table) {
            $table->dropColumn([
                'weather_forecastable_id',
                'weather_forecastable_type',
            ]);

            // Revert mission_id to non-nullable
            $table->dropForeign(['mission_id']);
            $table->unsignedBigInteger('mission_id')->nullable(false)->change();
            $table->foreign('mission_id')->references('id')->on('missions')->onDelete('cascade');
        });
    }
};
