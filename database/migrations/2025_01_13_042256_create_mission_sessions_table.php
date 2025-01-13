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
        Schema::create('mission_sessions', function (Blueprint $table) {
            $table->id();
            $table->ulid();

            $table->foreignId('mission_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('facilitator_id')
                ->constrained('members')
                ->cascadeOnDelete();
            $table->foreignId('speaker_id')
                ->nullable()
                ->constrained('members')
                ->cascadeOnDelete();
            $table->foreignId('class_group_id')
                ->nullable() // Null for sessions involving multiple class groups
                ->constrained()
                ->cascadeOnDelete();

            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->longText('notes');
            $table->integer('order')->default(0);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mission_sessions');
    }
};
