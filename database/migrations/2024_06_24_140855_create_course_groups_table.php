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
        Schema::create('course_groups', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();

            $table->foreignId('course_id')->constrained();
            $table->foreignId('group_id')->constrained();
            $table->date('start_date');

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['course_id', 'group_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_groups');
    }
};
