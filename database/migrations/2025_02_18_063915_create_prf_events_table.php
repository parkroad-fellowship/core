<?php

use App\Enums\PRFActiveStatus;
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
        Schema::create('prf_events', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();

            $table->string('name');
            $table->longText('description');
            $table->date('start_date');
            $table->date('end_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('capacity')->default(0);

            $table->string('venue')->nullable();
            $table->double('latitude')->nullable();
            $table->double('longitude')->nullable();

            $table->tinyInteger('status')->default(PRFActiveStatus::ACTIVE);

            $table->longText('dressing_recommendations')->nullable();
            $table->json('weather_recommendations')->default('[]');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prf_events');
    }
};
