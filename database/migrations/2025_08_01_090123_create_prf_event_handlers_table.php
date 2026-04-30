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
        Schema::create('prf_event_handlers', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();

            $table->foreignId('prf_event_id')
                ->constrained('prf_events')
                ->cascadeOnDelete();
            $table->foreignId('member_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prf_event_handlers');
    }
};
