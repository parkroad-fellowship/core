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
            $table->date('end_date')->nullable();
            $table->longText('mission_prep_notes')->nullable();
            $table->tinyInteger('status')->default(PRFMissionStatus::PENDING);

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
