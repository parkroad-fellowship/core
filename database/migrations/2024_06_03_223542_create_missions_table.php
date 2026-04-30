<?php

use App\Enums\PRFMissionStatus;
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
        Schema::create('missions', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();

            $table->foreignId('school_term_id')->constrained();
            $table->foreignId('mission_type_id')->constrained();
            $table->foreignId('school_id')->constrained();
            $table->date('start_date');
            $table->time('start_time');
            $table->date('end_date');
            $table->time('end_time');
            $table->longText('mission_prep_notes')->nullable();
            $table->integer('capacity')->nullable();
            $table->tinyInteger('status')->default(PRFMissionStatus::PENDING);
            $table->longText('dressing_recommendations')->nullable();
            $table->longText('activity_recommendations')->nullable();
            $table->longText('executive_summary')->nullable()->after('status');
            $table->text('whats_app_link')->nullable();
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
        Schema::dropIfExists('missions');
    }
};
