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
        Schema::create('weather_forecasts', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();

            $table->bigInteger('weather_forecastable_id');
            $table->tinyInteger('weather_forecastable_type');
            $table->dateTime('forecast_date');

            $table->integer('weather_code');
            $table->string('weather_code_description');

            $table->dateTime('moon_rise_time');
            $table->dateTime('moon_set_time');

            $table->dateTime('sun_rise_time');
            $table->dateTime('sun_set_time');

            $table->json('cloud_cover')->default('[]');
            $table->json('dew_point')->default('[]');
            $table->json('humidity')->default('[]');
            $table->json('precipitation_probability')->default('[]');
            $table->json('rain')->default('[]')->comment('Accumulation/Intensity/LWE');
            $table->json('temperature')->default('[]')->comment('Inclusive of apparent temperature');
            $table->json('uv')->default('[]');
            $table->json('visibility')->default('[]');
            $table->json('wind')->default('[]')->comment('Speed/Direction/Gust');

            $table->json('forecast_data')->default('[]');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weather_forecasts');
    }
};
