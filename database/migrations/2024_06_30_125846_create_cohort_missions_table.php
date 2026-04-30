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
        Schema::create('cohort_missions', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();

            $table->foreignId('cohort_id')->constrained();
            $table->foreignId('mission_id')->constrained();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['cohort_id', 'mission_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cohort_missions');
    }
};
