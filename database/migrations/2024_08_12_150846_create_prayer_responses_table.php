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
        Schema::create('prayer_responses', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();

            $table->foreignId('prayer_prompt_id')
                ->constrained()
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
        Schema::dropIfExists('prayer_responses');
    }
};
