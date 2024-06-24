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
        Schema::create('souls', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();

            $table->foreignId('mission_id')->constrained();
            $table->foreignId('class_group_id')->constrained();
            $table->string('full_name');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('souls');
    }
};
